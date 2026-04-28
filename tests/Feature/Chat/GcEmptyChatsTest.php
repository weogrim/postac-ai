<?php

declare(strict_types=1);

use App\Character\Models\CharacterModel;
use App\Chat\Enums\SenderRole;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageModel;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('hard deletes chats older than threshold with only greeting', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->create();

    $chat = ChatModel::create(['user_id' => $user->id, 'character_id' => $character->id]);
    MessageModel::create([
        'chat_id' => $chat->id,
        'sender_role' => SenderRole::Character,
        'character_id' => $character->id,
        'content' => 'greeting',
    ]);
    $chat->forceFill(['created_at' => now()->subDays(8)])->save();

    $this->artisan('chats:gc-empty', ['--days' => 7])->assertExitCode(0);

    expect(ChatModel::withTrashed()->find($chat->id))->toBeNull();
});

it('keeps chats with at least one user message regardless of age', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->create();

    $chat = ChatModel::create(['user_id' => $user->id, 'character_id' => $character->id]);
    MessageModel::create([
        'chat_id' => $chat->id,
        'sender_role' => SenderRole::User,
        'user_id' => $user->id,
        'content' => 'cześć',
    ]);
    $chat->forceFill(['created_at' => now()->subDays(30)])->save();

    $this->artisan('chats:gc-empty', ['--days' => 7])->assertExitCode(0);

    expect(ChatModel::find($chat->id))->not->toBeNull();
});

it('keeps recent empty chats', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->create();

    $chat = ChatModel::create(['user_id' => $user->id, 'character_id' => $character->id]);
    MessageModel::create([
        'chat_id' => $chat->id,
        'sender_role' => SenderRole::Character,
        'character_id' => $character->id,
        'content' => 'greeting',
    ]);

    $this->artisan('chats:gc-empty', ['--days' => 7])->assertExitCode(0);

    expect(ChatModel::find($chat->id))->not->toBeNull();
});
