<?php

declare(strict_types=1);

use App\Character\Models\CharacterModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('filters /characters by ?official=true', function () {
    /** @var TestCase $this */
    $official = CharacterModel::factory()->create(['name' => 'Oficjalna Postać', 'is_official' => true]);
    $normal = CharacterModel::factory()->create(['name' => 'Zwykła Postać', 'is_official' => false]);

    $this->get('/characters?official=1')
        ->assertOk()
        ->assertSee('Oficjalna Postać')
        ->assertDontSee('Zwykła Postać');
});

it('shows all characters without official filter', function () {
    /** @var TestCase $this */
    CharacterModel::factory()->create(['name' => 'Oficjalna Postać', 'is_official' => true]);
    CharacterModel::factory()->create(['name' => 'Zwykła Postać', 'is_official' => false]);

    $this->get('/characters')
        ->assertOk()
        ->assertSee('Oficjalna Postać')
        ->assertSee('Zwykła Postać');
});

it('renders official toggle checkbox on /characters', function () {
    /** @var TestCase $this */
    $this->get('/characters')
        ->assertOk()
        ->assertSee('Tylko oficjalne');
});
