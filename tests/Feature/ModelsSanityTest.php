<?php

declare(strict_types=1);

use App\Messaging\SenderRole;
use App\Models\Character;
use App\Models\Chat;
use App\Models\Message;
use App\Models\MessageLimit;
use App\Models\User;
use App\Settings\ChatSettings;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

it('creates a chat with messages from user and character', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user, 'author')->create();
    $chat = Chat::factory()->create([
        'user_id' => $user->id,
        'character_id' => $character->id,
    ]);

    $userMsg = Message::factory()->fromUser($chat)->create();
    $charMsg = Message::factory()->fromCharacter($chat)->create();

    expect($userMsg->sender_role)->toBe(SenderRole::User);
    expect($userMsg->sender()->is($user))->toBeTrue();
    expect($charMsg->sender_role)->toBe(SenderRole::Character);
    expect($charMsg->sender()->is($character))->toBeTrue();
    expect($chat->messages()->count())->toBe(2);
});

it('rejects duplicate active chat for same user/character pair', function () {
    $user = User::factory()->create();
    $character = Character::factory()->create();
    Chat::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);

    expect(fn () => Chat::factory()->create([
        'user_id' => $user->id,
        'character_id' => $character->id,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('allows the same pair after the first chat is soft deleted', function () {
    $user = User::factory()->create();
    $character = Character::factory()->create();
    $first = Chat::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);
    $first->delete();

    $second = Chat::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);

    expect($second->id)->not->toBe($first->id);
});

it('enforces sender_role check constraint', function () {
    $chat = Chat::factory()->create();

    expect(fn () => Message::factory()->create([
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
    $user = User::factory()->create();
    MessageLimit::factory()->for($user)->create(['used' => 0, 'quota' => 5]);
    MessageLimit::factory()->for($user)->exhausted()->create();
    MessageLimit::factory()->for($user)->create([
        'period_start' => now()->subDays(2),
        'used' => 0,
    ]);

    $available = MessageLimit::query()
        ->forUser($user)
        ->available()
        ->forCurrentWindow()
        ->count();

    expect($available)->toBe(1);
});
