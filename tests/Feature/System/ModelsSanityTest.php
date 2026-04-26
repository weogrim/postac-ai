<?php

declare(strict_types=1);

use App\Character\Models\CharacterModel;
use App\Chat\Enums\SenderRole;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageLimitModel;
use App\Chat\Models\MessageModel;
use App\Chat\Settings\ChatSettings;
use App\User\Models\UserModel;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

it('creates a chat with messages from user and character', function () {
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->for($user, 'author')->create();
    $chat = ChatModel::factory()->create([
        'user_id' => $user->id,
        'character_id' => $character->id,
    ]);

    $userMsg = MessageModel::factory()->fromUser($chat)->create();
    $charMsg = MessageModel::factory()->fromCharacter($chat)->create();

    expect($userMsg->sender_role)->toBe(SenderRole::User);
    expect($userMsg->sender()->is($user))->toBeTrue();
    expect($charMsg->sender_role)->toBe(SenderRole::Character);
    expect($charMsg->sender()->is($character))->toBeTrue();
    expect($chat->messages()->count())->toBe(2);
});

it('rejects duplicate active chat for same user/character pair', function () {
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->create();
    ChatModel::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);

    expect(fn () => ChatModel::factory()->create([
        'user_id' => $user->id,
        'character_id' => $character->id,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('allows the same pair after the first chat is soft deleted', function () {
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->create();
    $first = ChatModel::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);
    $first->delete();

    $second = ChatModel::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);

    expect($second->id)->not->toBe($first->id);
});

it('enforces sender_role check constraint', function () {
    $chat = ChatModel::factory()->create();

    expect(fn () => MessageModel::factory()->create([
        'chat_id' => $chat->id,
        'sender_role' => SenderRole::User,
        'user_id' => null,
        'character_id' => null,
        'content' => 'invalid',
    ]))->toThrow(QueryException::class);
});

it('loads ChatSettings with defaults from migration', function () {
    $settings = app(ChatSettings::class);

    expect($settings->historyLength)->toBe(20);
    expect($settings->temperature)->toBe(0.9);
    expect($settings->maxTokens)->toBe(1024);
});

it('scopes message limits to available and current window', function () {
    $user = UserModel::factory()->create();
    MessageLimitModel::factory()->for($user, 'user')->create(['used' => 0, 'quota' => 5]);
    MessageLimitModel::factory()->for($user, 'user')->exhausted()->create();
    MessageLimitModel::factory()->for($user, 'user')->create([
        'period_start' => now()->subDays(2),
        'used' => 0,
    ]);

    $available = MessageLimitModel::query()
        ->forUser($user)
        ->available()
        ->forCurrentWindow()
        ->count();

    expect($available)->toBe(1);
});
