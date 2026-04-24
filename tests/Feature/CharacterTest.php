<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('shows the create form to verified users', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/characters/create')
        ->assertOk()
        ->assertSee('Nowa postać')
        ->assertSee('Prompt');
});

it('redirects guests from create form', function () {
    /** @var TestCase $this */
    $this->get('/characters/create')
        ->assertRedirect(route('login'));
});

it('requires email verification for create form', function () {
    /** @var TestCase $this */
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/characters/create')
        ->assertRedirect(route('verification.notice'));
});

it('stores a character without avatar and redirects to chat', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/characters', [
        'name' => 'Ada Lovelace',
        'prompt' => 'Matematyczka i pierwsza programistka.',
    ]);

    $response->assertRedirect();
    $character = Character::where('name', 'Ada Lovelace')->firstOrFail();
    expect($character->user_id)->toBe($user->id);
    $response->assertRedirectToRoute('chat.show', ['chat' => $character->chats()->firstOrFail()]);
});

it('stores a character with uploaded avatar', function () {
    /** @var TestCase $this */
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/characters', [
        'name' => 'Marie Curie',
        'prompt' => 'Laureatka dwóch Nagród Nobla.',
        'avatar' => UploadedFile::fake()->image('avatar.png', 512, 512),
    ]);

    $response->assertRedirect();
    $character = Character::where('name', 'Marie Curie')->firstOrFail();
    expect($character->getMedia('avatar')->count())->toBe(1);
});

it('rejects empty name or prompt', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/characters', [])
        ->assertSessionHasErrors(['name', 'prompt']);
});
