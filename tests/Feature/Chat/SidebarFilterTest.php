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

it('hides chats without user messages from sidebar', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $charA = CharacterModel::factory()->create(['name' => 'Postać A']);
    $charB = CharacterModel::factory()->create(['name' => 'Postać B']);

    $chatA = ChatModel::create(['user_id' => $user->id, 'character_id' => $charA->id]);
    MessageModel::create([
        'chat_id' => $chatA->id,
        'sender_role' => SenderRole::User,
        'user_id' => $user->id,
        'content' => 'cześć',
    ]);

    $chatB = ChatModel::create(['user_id' => $user->id, 'character_id' => $charB->id]);
    MessageModel::create([
        'chat_id' => $chatB->id,
        'sender_role' => SenderRole::Character,
        'character_id' => $charB->id,
        'content' => 'Witaj — to jest greeting.',
    ]);

    auth()->login($user);

    $response = $this->get(route('chat.show', $chatA));

    $response->assertOk();
    $response->assertDontSee(route('chat.show', $chatB));
});

it('shows chats with at least one user message in sidebar', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $charA = CharacterModel::factory()->create();
    $charB = CharacterModel::factory()->create();

    $chatA = ChatModel::create(['user_id' => $user->id, 'character_id' => $charA->id]);
    MessageModel::create([
        'chat_id' => $chatA->id,
        'sender_role' => SenderRole::User,
        'user_id' => $user->id,
        'content' => 'A',
    ]);

    $chatB = ChatModel::create(['user_id' => $user->id, 'character_id' => $charB->id]);
    MessageModel::create([
        'chat_id' => $chatB->id,
        'sender_role' => SenderRole::User,
        'user_id' => $user->id,
        'content' => 'B',
    ]);

    auth()->login($user);

    $response = $this->get(route('chat.show', $chatA));

    $response->assertOk();
    $response->assertSee(route('chat.show', $chatB));
});

it('redirects /chat to home when user only has empty chats', function () {
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

    auth()->login($user);

    $this->get('/chat')->assertRedirect(route('home'));
});

it('redirects /chat to latest chat with user messages', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $charEmpty = CharacterModel::factory()->create();
    $charActive = CharacterModel::factory()->create();

    $emptyChat = ChatModel::create(['user_id' => $user->id, 'character_id' => $charEmpty->id]);
    MessageModel::create([
        'chat_id' => $emptyChat->id,
        'sender_role' => SenderRole::Character,
        'character_id' => $charEmpty->id,
        'content' => 'greeting',
    ]);

    $activeChat = ChatModel::create(['user_id' => $user->id, 'character_id' => $charActive->id]);
    MessageModel::create([
        'chat_id' => $activeChat->id,
        'sender_role' => SenderRole::User,
        'user_id' => $user->id,
        'content' => 'cześć',
    ]);

    auth()->login($user);

    $this->get('/chat')->assertRedirect(route('chat.show', $activeChat));
});
