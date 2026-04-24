<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('redirects /chat to latest chat when user has chats', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id]);
    $chat = Chat::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);

    $this->actingAs($user)
        ->get('/chat')
        ->assertRedirect(route('chat.show', $chat));
});

it('redirects /chat to home when user has no chats', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/chat')
        ->assertRedirect(route('home'));
});

it('firstOrCreates chat on store and is idempotent', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post('/chat', ['character_id' => $character->id])
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/chat', ['character_id' => $character->id])
        ->assertRedirect();

    expect(Chat::query()->count())->toBe(1);
});

it('validates character_id on store', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/chat', ['character_id' => '01nonexistentxyz'])
        ->assertSessionHasErrors('character_id');
});

it('shows chat to its owner', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Hermes']);
    $chat = Chat::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);

    $this->actingAs($user)
        ->get(route('chat.show', $chat))
        ->assertOk()
        ->assertSee('Hermes');
});

it('returns 404 when viewing another user chat', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $owner->id]);
    $chat = Chat::factory()->create(['user_id' => $owner->id, 'character_id' => $character->id]);

    $this->actingAs($intruder)
        ->get(route('chat.show', $chat))
        ->assertNotFound();
});

it('requires auth for chat routes', function () {
    /** @var TestCase $this */
    $this->get('/chat')->assertRedirect(route('login'));
    $this->post('/chat', ['character_id' => 'x'])->assertRedirect(route('login'));
});
