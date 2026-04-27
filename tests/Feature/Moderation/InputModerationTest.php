<?php

declare(strict_types=1);

use App\Character\Models\CharacterModel;
use App\Chat\Enums\SenderRole;
use App\Chat\Models\ChatModel;
use App\Moderation\Contracts\ModerationProvider;
use App\Moderation\HelplineMessage;
use App\Moderation\Models\SafetyEventModel;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Ai\AnonymousAgent;
use Tests\Support\FakeModerationProvider;
use Tests\TestCase;

uses(RefreshDatabase::class);

/**
 * @return array{0: UserModel, 1: ChatModel}
 */
function moderationSeed(): array
{
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->create();
    $chat = ChatModel::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);

    return [$user, $chat];
}

it('blocks input flagged by moderation with HTMX 422', function () {
    /** @var TestCase $this */
    [$user, $chat] = moderationSeed();

    app()->bind(ModerationProvider::class, fn () => new FakeModerationProvider(
        flagged: true,
        categories: ['sexual' => 0.92],
    ));

    $response = $this->actingAs($user)
        ->withHeader('HX-Request', 'true')
        ->post(route('message.store', $chat), ['content' => 'NSFW prompt']);

    $response->assertStatus(422);
    $response->assertSee('zmieńmy temat', false);
    expect($chat->messages()->count())->toBe(0);
});

it('detects self-harm input and replies with helpline content + logs SafetyEvent', function () {
    /** @var TestCase $this */
    [$user, $chat] = moderationSeed();

    app()->bind(ModerationProvider::class, fn () => new FakeModerationProvider(
        flagged: true,
        categories: ['self-harm' => 0.91],
    ));

    $response = $this->actingAs($user)
        ->post(route('message.store', $chat), ['content' => 'chcę się zabić']);

    $response->assertCreated();
    $response->assertHeaderMissing('X-Character-Message-Id');

    $charMsg = $chat->messages()
        ->where('sender_role', SenderRole::Character->value)
        ->first();

    expect($charMsg)->not->toBeNull();
    expect($charMsg->content)->toBe(app(HelplineMessage::class)->polish());

    expect(SafetyEventModel::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('blocks further messages after self-harm rate limit threshold', function () {
    /** @var TestCase $this */
    [$user, $chat] = moderationSeed();

    RateLimiter::clear('selfharm:'.$user->id);

    app()->bind(ModerationProvider::class, fn () => new FakeModerationProvider(
        flagged: true,
        categories: ['self-harm' => 0.95],
    ));

    foreach (range(1, 3) as $_) {
        $this->actingAs($user)
            ->post(route('message.store', $chat), ['content' => 'crisis'])
            ->assertCreated();
    }

    $this->actingAs($user)
        ->post(route('message.store', $chat), ['content' => 'next attempt'])
        ->assertForbidden();
});

it('passes input through when NoOp provider used (default in tests)', function () {
    /** @var TestCase $this */
    [$user, $chat] = moderationSeed();

    AnonymousAgent::fake(['hej']);

    $this->actingAs($user)
        ->post(route('message.store', $chat), ['content' => 'normalne pytanie'])
        ->assertCreated()
        ->assertHeader('X-Character-Message-Id');
});
