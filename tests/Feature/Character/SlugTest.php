<?php

declare(strict_types=1);

use App\Character\Enums\CharacterKind;
use App\Character\Models\CharacterModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('auto-generates slug for regular character on create', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create(['name' => 'Józef Piłsudski']);

    expect($character->slug)->toBe('jozef-pilsudski');
});

it('does not regenerate slug when name changes', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create(['name' => 'Pierwsza Nazwa']);
    $original = $character->slug;

    $character->update(['name' => 'Inna Nazwa']);

    expect($character->fresh()->slug)->toBe($original);
});

it('respects admin-provided slug override', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create([
        'name' => 'Coś Tam',
        'slug' => 'custom-admin-slug',
    ]);

    expect($character->slug)->toBe('custom-admin-slug');
});

it('dedups slug with numeric suffix when name collides', function () {
    /** @var TestCase $this */
    CharacterModel::factory()->create(['name' => 'Maria Curie']);
    $second = CharacterModel::factory()->create(['name' => 'Maria Curie']);
    $third = CharacterModel::factory()->create(['name' => 'Maria Curie']);

    expect($second->slug)->toBe('maria-curie-2');
    expect($third->slug)->toBe('maria-curie-3');
});

it('does not generate slug for dating characters', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create([
        'name' => 'Maja',
        'kind' => CharacterKind::Dating,
    ]);

    expect($character->slug)->toBeNull();
});

it('resolves /postacie/{slug} route to character profile', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create(['name' => 'Adam Mickiewicz']);

    $this->get('/postacie/'.$character->slug)
        ->assertOk()
        ->assertSee('Adam Mickiewicz');
});

it('returns 404 for old ULID URL after migration', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create();

    $this->get('/characters/'.$character->id)->assertNotFound();
});
