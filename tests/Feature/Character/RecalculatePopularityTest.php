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

it('recalculates popularity_24h based on distinct chats with messages in last 24h', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create(['popularity_24h' => 0]);

    $user1 = UserModel::factory()->create();
    $user2 = UserModel::factory()->create();
    $user3 = UserModel::factory()->create();

    foreach ([$user1, $user2, $user3] as $user) {
        $chat = ChatModel::create([
            'user_id' => $user->id,
            'character_id' => $character->id,
        ]);

        MessageModel::create([
            'chat_id' => $chat->id,
            'sender_role' => SenderRole::User,
            'user_id' => $user->id,
            'content' => 'cześć',
        ]);
    }

    $this->artisan('characters:recalc-popularity')->assertExitCode(0);

    expect($character->fresh()->popularity_24h)->toBe(3);
});

it('ignores messages older than 24 hours', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create(['popularity_24h' => 5]);

    $user = UserModel::factory()->create();
    $chat = ChatModel::create([
        'user_id' => $user->id,
        'character_id' => $character->id,
    ]);

    $oldMessage = MessageModel::create([
        'chat_id' => $chat->id,
        'sender_role' => SenderRole::User,
        'user_id' => $user->id,
        'content' => 'stara wiadomość',
    ]);

    $oldMessage->forceFill(['created_at' => now()->subDays(2)])->save();

    $this->artisan('characters:recalc-popularity')->assertExitCode(0);

    expect($character->fresh()->popularity_24h)->toBe(0);
});
