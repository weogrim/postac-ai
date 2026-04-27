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

it('inserts greeting as first character message when chat is created', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->create([
        'greeting' => 'Witaj. Jestem tu, żeby porozmawiać.',
    ]);

    $this->actingAs($user)->post('/chat', [
        'character_id' => $character->id,
    ])->assertRedirect();

    $chat = ChatModel::where('user_id', $user->id)->where('character_id', $character->id)->firstOrFail();
    $message = $chat->messages()->first();

    expect($message)->not->toBeNull();
    expect($message->sender_role)->toBe(SenderRole::Character);
    expect($message->content)->toBe('Witaj. Jestem tu, żeby porozmawiać.');
    expect($message->character_id)->toBe($character->id);
});

it('does not insert greeting when character has no greeting set', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->create(['greeting' => null]);

    $this->actingAs($user)->post('/chat', ['character_id' => $character->id]);

    $chat = ChatModel::where('user_id', $user->id)->where('character_id', $character->id)->firstOrFail();
    expect($chat->messages()->count())->toBe(0);
});

it('does not duplicate greeting on subsequent visits to existing chat', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->create([
        'greeting' => 'Witaj.',
    ]);

    $this->actingAs($user)->post('/chat', ['character_id' => $character->id]);
    $this->actingAs($user)->post('/chat', ['character_id' => $character->id]);

    $chat = ChatModel::where('user_id', $user->id)->where('character_id', $character->id)->firstOrFail();
    expect(MessageModel::where('chat_id', $chat->id)->count())->toBe(1);
});
