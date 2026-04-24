<?php

declare(strict_types=1);

use App\AI\ModelType;
use App\Messaging\SenderRole;
use App\Models\Character;
use App\Models\Chat;
use App\Models\MessageLimit;
use App\Models\User;
use App\Premium\LimitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Chat}
 */
function seedChatForQuota(): array
{
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id]);
    $chat = Chat::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);

    return [$user, $chat];
}

it('store succeeds when user has fresh limits and consumes one slot', function () {
    /** @var TestCase $this */
    [$user, $chat] = seedChatForQuota();

    $response = $this->actingAs($user)
        ->post("/chat/{$chat->id}/messages", ['content' => 'Cześć!']);

    $response->assertCreated();

    expect(MessageLimit::forUser($user)->sum('used'))->toBe(1);

    $charMsg = $chat->messages()->where('sender_role', SenderRole::Character->value)->first();
    expect($charMsg->model)->toBe(ModelType::Gpt4o->value);
});

it('store returns 403 when user is out of messages', function () {
    /** @var TestCase $this */
    [$user, $chat] = seedChatForQuota();

    MessageLimit::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4o,
        'limit_type' => LimitType::Daily,
        'quota' => 5,
        'used' => 5,
        'priority' => 2,
        'period_start' => now(),
    ]);
    MessageLimit::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4oMini,
        'limit_type' => LimitType::Daily,
        'quota' => 20,
        'used' => 20,
        'priority' => 1,
        'period_start' => now(),
    ]);

    $response = $this->actingAs($user)
        ->post("/chat/{$chat->id}/messages", ['content' => 'Hej']);

    $response->assertForbidden();
    expect($chat->messages()->count())->toBe(0);
});

it('store returns HTMX toast + 403 + HX-Reswap none when OOM and HX-Request', function () {
    /** @var TestCase $this */
    [$user, $chat] = seedChatForQuota();

    MessageLimit::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4o,
        'limit_type' => LimitType::Daily,
        'quota' => 5,
        'used' => 5,
        'priority' => 2,
        'period_start' => now(),
    ]);
    MessageLimit::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4oMini,
        'limit_type' => LimitType::Daily,
        'quota' => 20,
        'used' => 20,
        'priority' => 1,
        'period_start' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withHeader('HX-Request', 'true')
        ->post("/chat/{$chat->id}/messages", ['content' => 'Hej']);

    $response->assertForbidden();
    $response->assertHeader('HX-Reswap', 'none');
    $response->assertSee('Limit wiadomości wyczerpany');
    $response->assertSee('hx-swap-oob', false);
});

it('store uses ChatSettings default model for premium users (no MessageLimit mutation)', function () {
    /** @var TestCase $this */
    [, $chat] = seedChatForQuota();
    $user = User::factory()->premium()->create();
    $chat->update(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->post("/chat/{$chat->id}/messages", ['content' => 'Cześć!']);

    $response->assertCreated();
    expect(MessageLimit::forUser($user)->count())->toBe(0);
});
