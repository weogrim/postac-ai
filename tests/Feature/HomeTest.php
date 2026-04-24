<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('renders home page for guests with character listing', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $character = Character::factory()->create([
        'user_id' => $user->id,
        'name' => 'Sherlock Holmes',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Sherlock Holmes')
        ->assertSee('Zaloguj');
});

it('renders home page for authenticated users with add-character CTA', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('Dodaj postać');
});

it('shows empty state when no characters exist', function () {
    /** @var TestCase $this */
    $this->get('/')
        ->assertOk()
        ->assertSee('Brak postaci');
});

it('guest card links to login instead of posting to chat', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    Character::factory()->create(['user_id' => $user->id]);

    $response = $this->get('/');

    $response->assertOk();
    expect($response->getContent())
        ->toContain(route('login'));
});
