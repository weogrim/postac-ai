<?php

declare(strict_types=1);

use App\Character\Models\CharacterModel;
use App\Chat\Enums\LimitType;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageLimitModel;
use App\Chat\Settings\ChatSettings;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Ai\AnonymousAgent;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    RateLimiter::clear('ghost:127.0.0.1');
});

it('creates a ghost user on POST /chat by anonymous visitor', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create();

    $response = $this->post('/chat', ['character_id' => $character->id]);

    $response->assertRedirect();

    expect(UserModel::query()->guests()->count())->toBe(1);

    $ghost = UserModel::query()->guests()->first();
    expect($ghost->name)->toBe('Gość');
    expect($ghost->email)->toBeNull();
    expect(Auth::id())->toBe($ghost->id);
    expect(ChatModel::query()->where('user_id', $ghost->id)->count())->toBe(1);
});

it('reuses existing ghost session when same browser sends multiple chats', function () {
    /** @var TestCase $this */
    $charA = CharacterModel::factory()->create();
    $charB = CharacterModel::factory()->create();

    $this->post('/chat', ['character_id' => $charA->id]);
    $this->post('/chat', ['character_id' => $charB->id]);

    expect(UserModel::query()->guests()->count())->toBe(1);
    expect(ChatModel::query()->count())->toBe(2);
});

it('grants 5 guest message slots and blocks after limit', function () {
    /** @var TestCase $this */
    AnonymousAgent::fake(['hej']);

    $character = CharacterModel::factory()->create();
    $this->post('/chat', ['character_id' => $character->id]);
    $chat = ChatModel::query()->first();

    foreach (range(1, 5) as $i) {
        $this->post(route('message.store', $chat), ['content' => "msg {$i}"])
            ->assertCreated();
    }

    /** @var UserModel $ghost */
    $ghost = UserModel::query()->guests()->first();
    $limit = MessageLimitModel::query()
        ->where('user_id', $ghost->id)
        ->where('limit_type', LimitType::Guest->value)
        ->first();

    expect($limit->used)->toBe(5);
    expect($limit->quota)->toBe(5);

    $this->post(route('message.store', $chat), ['content' => '6'])
        ->assertForbidden();
});

it('returns guest gate fragment with HTMX header on overlimit', function () {
    /** @var TestCase $this */
    $ghost = UserModel::factory()->create([
        'email' => null,
        'password' => null,
        'birthdate' => null,
        'email_verified_at' => null,
    ]);
    $character = CharacterModel::factory()->create();
    $chat = ChatModel::factory()->create([
        'user_id' => $ghost->id,
        'character_id' => $character->id,
    ]);
    MessageLimitModel::create([
        'user_id' => $ghost->id,
        'model_type' => app(ChatSettings::class)->defaultModel,
        'limit_type' => LimitType::Guest,
        'priority' => 0,
        'quota' => 5,
        'used' => 5,
        'period_start' => null,
    ]);

    $response = $this->actingAs($ghost)->withHeader('HX-Request', 'true')
        ->post(route('message.store', $chat), ['content' => 'jeden więcej']);

    $response->assertForbidden();
    $response->assertSee('register-gate', false);
    $response->assertSee('Zarejestruj się', false);
});

it('renders persistent gate bar after refresh when guest is over limit', function () {
    /** @var TestCase $this */
    $ghost = UserModel::factory()->create([
        'email' => null,
        'password' => null,
        'birthdate' => null,
        'email_verified_at' => null,
    ]);
    $character = CharacterModel::factory()->create();
    $chat = ChatModel::factory()->create([
        'user_id' => $ghost->id,
        'character_id' => $character->id,
    ]);
    MessageLimitModel::create([
        'user_id' => $ghost->id,
        'model_type' => app(ChatSettings::class)->defaultModel,
        'limit_type' => LimitType::Guest,
        'priority' => 0,
        'quota' => 5,
        'used' => 5,
        'period_start' => null,
    ]);

    $response = $this->actingAs($ghost)->get(route('chat.show', $chat));

    $response->assertOk();
    $response->assertSee('Załóż konto, żeby pisać dalej.', false);
    $response->assertDontSee('hx-post=', false);
    $response->assertSee('register-gate', false);
});

it('rate-limits ghost creation by IP at 5 per minute', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create();

    foreach (range(1, 5) as $_) {
        Auth::logout();
        session()->flush();
        $this->post('/chat', ['character_id' => $character->id])->assertRedirect();
    }

    Auth::logout();
    session()->flush();

    $response = $this->post('/chat', ['character_id' => $character->id]);
    $response->assertStatus(429);
});

it('upgrade flow: ghost registering email/password keeps chats', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create();
    $this->post('/chat', ['character_id' => $character->id]);

    /** @var UserModel $ghost */
    $ghost = UserModel::query()->guests()->first();

    $this->post('/register', [
        'name' => 'Janek',
        'email' => 'janek@example.com',
        'password' => 'StrongPass123!',
        'password_confirmation' => 'StrongPass123!',
        'birthdate' => now()->subYears(25)->toDateString(),
        'accepted_terms' => '1',
        'accepted_privacy' => '1',
    ])->assertRedirect(route('verification.notice'));

    $upgraded = UserModel::query()->find($ghost->id);
    expect($upgraded)->not->toBeNull();
    expect($upgraded->email)->toBe('janek@example.com');
    expect($upgraded->isGuest())->toBeFalse();
    expect(ChatModel::query()->where('user_id', $upgraded->id)->count())->toBe(1);
});

it('upgrade conflict: existing email kicks ghost and logs into the existing account', function () {
    /** @var TestCase $this */
    $existing = UserModel::factory()->create([
        'email' => 'janek@example.com',
        'name' => 'JanekOriginal',
    ]);

    $character = CharacterModel::factory()->create();
    $this->post('/chat', ['character_id' => $character->id]);

    /** @var UserModel $ghost */
    $ghost = UserModel::query()->guests()->first();

    $this->post('/register', [
        'name' => 'JanekNowy',
        'email' => 'janek@example.com',
        'password' => 'StrongPass123!',
        'password_confirmation' => 'StrongPass123!',
        'birthdate' => now()->subYears(25)->toDateString(),
        'accepted_terms' => '1',
        'accepted_privacy' => '1',
    ])->assertRedirect(route('home'));

    expect(UserModel::query()->find($ghost->id))->toBeNull();
    expect(Auth::id())->toBe($existing->id);
});
