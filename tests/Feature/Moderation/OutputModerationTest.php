<?php

declare(strict_types=1);

use App\Character\Models\CharacterModel;
use App\Chat\Enums\SenderRole;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageModel;
use App\Moderation\Contracts\ModerationProvider;
use App\Moderation\HelplineMessage;
use App\Moderation\Models\SafetyEventModel;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\AnonymousAgent;
use Tests\Support\FakeModerationProvider;
use Tests\TestCase;

uses(RefreshDatabase::class);

/**
 * Bind moderation provider per-call: input check uses NoOp (let request through),
 * output check uses fake (flag the AI response).
 */
function bindOutputOnlyModeration(bool $flaggedSelfHarm = false, bool $flaggedOther = false): void
{
    $count = 0;

    app()->bind(ModerationProvider::class, function () use (&$count, $flaggedSelfHarm, $flaggedOther) {
        $count++;

        if ($count === 1) {
            // input check: pass through
            return new FakeModerationProvider(flagged: false, categories: []);
        }

        // output check: flag according to params
        if ($flaggedSelfHarm) {
            return new FakeModerationProvider(flagged: true, categories: ['self-harm' => 0.93]);
        }
        if ($flaggedOther) {
            return new FakeModerationProvider(flagged: true, categories: ['sexual' => 0.88]);
        }

        return new FakeModerationProvider(flagged: false, categories: []);
    });
}

it('replaces flagged AI output with fallback message', function () {
    /** @var TestCase $this */
    AnonymousAgent::fake(['NSFW response from model']);
    bindOutputOnlyModeration(flaggedOther: true);

    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->create();
    $chat = ChatModel::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);

    $this->actingAs($user)->post(route('message.store', $chat), ['content' => 'pytanie']);

    $response = $this->actingAs($user)->get(route('message.stream', $chat));
    @$response->baseResponse->sendContent();

    $charMsg = MessageModel::query()
        ->where('chat_id', $chat->id)
        ->where('sender_role', SenderRole::Character->value)
        ->first();

    expect($charMsg->content)->toBe(app(HelplineMessage::class)->fallback());
});

it('overrides flagged self-harm output with helpline + logs SafetyEvent', function () {
    /** @var TestCase $this */
    AnonymousAgent::fake(['Może się zabij']);
    bindOutputOnlyModeration(flaggedSelfHarm: true);

    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->create();
    $chat = ChatModel::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);

    $this->actingAs($user)->post(route('message.store', $chat), ['content' => 'pytanie']);

    $response = $this->actingAs($user)->get(route('message.stream', $chat));
    @$response->baseResponse->sendContent();

    $charMsg = MessageModel::query()
        ->where('chat_id', $chat->id)
        ->where('sender_role', SenderRole::Character->value)
        ->first();

    expect($charMsg->content)->toBe(app(HelplineMessage::class)->polish());
    expect(SafetyEventModel::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('keeps original output when moderation passes', function () {
    /** @var TestCase $this */
    AnonymousAgent::fake(['Hej, miło Cię poznać!']);
    bindOutputOnlyModeration();

    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->create();
    $chat = ChatModel::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);

    $this->actingAs($user)->post(route('message.store', $chat), ['content' => 'Cześć']);

    $response = $this->actingAs($user)->get(route('message.stream', $chat));
    @$response->baseResponse->sendContent();

    $charMsg = MessageModel::query()
        ->where('chat_id', $chat->id)
        ->where('sender_role', SenderRole::Character->value)
        ->first();

    expect($charMsg->content)->toBe('Hej, miło Cię poznać!');
});
