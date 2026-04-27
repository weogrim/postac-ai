<?php

declare(strict_types=1);

use App\Character\Enums\CharacterKind;
use App\Character\Models\CharacterModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Tags\Tag;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('shows the index page with characters', function () {
    /** @var TestCase $this */
    CharacterModel::factory()->create(['name' => 'Józef Piłsudski']);
    CharacterModel::factory()->create(['name' => 'Maria Curie']);

    $this->get('/characters')
        ->assertOk()
        ->assertSee('Józef Piłsudski')
        ->assertSee('Maria Curie');
});

it('filters characters by name search', function () {
    /** @var TestCase $this */
    CharacterModel::factory()->create(['name' => 'Józef Piłsudski']);
    CharacterModel::factory()->create(['name' => 'Maria Curie']);

    $response = $this->get('/characters?q=Pi%C5%82sudski');

    $response->assertOk()
        ->assertSee('Józef Piłsudski')
        ->assertDontSee('Maria Curie');
});

it('filters by description content', function () {
    /** @var TestCase $this */
    CharacterModel::factory()->create([
        'name' => 'Postać A',
        'description' => 'Ekspert od matematyki',
    ]);
    CharacterModel::factory()->create([
        'name' => 'Postać B',
        'description' => 'Historyk',
    ]);

    $this->get('/characters?q=matematyk')
        ->assertOk()
        ->assertSee('Postać A')
        ->assertDontSee('Postać B');
});

it('hides dating characters from index', function () {
    /** @var TestCase $this */
    CharacterModel::factory()->create([
        'name' => 'Regular One',
        'kind' => CharacterKind::Regular,
    ]);
    CharacterModel::factory()->create([
        'name' => 'Dating One',
        'kind' => CharacterKind::Dating,
    ]);

    $this->get('/characters')
        ->assertOk()
        ->assertSee('Regular One')
        ->assertDontSee('Dating One');
});

it('filters by category slug', function () {
    /** @var TestCase $this */
    $tag = Tag::findOrCreateFromString('Historia', 'category');

    $historyChar = CharacterModel::factory()->create(['name' => 'Historic Char']);
    $historyChar->attachTag($tag);

    CharacterModel::factory()->create(['name' => 'Other Char']);

    $this->get('/characters?category=historia')
        ->assertOk()
        ->assertSee('Historic Char')
        ->assertDontSee('Other Char');
});

it('sorts by popularity by default', function () {
    /** @var TestCase $this */
    CharacterModel::factory()->create([
        'name' => 'Less Popular',
        'popularity_24h' => 1,
    ]);
    CharacterModel::factory()->create([
        'name' => 'Most Popular',
        'popularity_24h' => 100,
    ]);

    $response = $this->get('/characters?sort=popular');
    $body = $response->getContent();

    $response->assertOk();
    expect(strpos($body, 'Most Popular'))->toBeLessThan(strpos($body, 'Less Popular'));
});

it('sorts by created_at desc when sort=new', function () {
    /** @var TestCase $this */
    CharacterModel::factory()->create([
        'name' => 'Older',
        'created_at' => now()->subDays(10),
    ]);
    CharacterModel::factory()->create([
        'name' => 'Newer',
        'created_at' => now()->subDay(),
    ]);

    $response = $this->get('/characters?sort=new');
    $body = $response->getContent();

    $response->assertOk();
    expect(strpos($body, 'Newer'))->toBeLessThan(strpos($body, 'Older'));
});

it('search endpoint returns HTMX grid fragment', function () {
    /** @var TestCase $this */
    CharacterModel::factory()->create(['name' => 'Wyszukiwany']);

    $response = $this->get('/characters/search?q=Wyszukiwany');

    $response->assertOk()
        ->assertSee('Wyszukiwany');
});

it('shows character profile page', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create([
        'name' => 'Profil Test',
        'description' => 'Opis postaci.',
    ]);

    $this->get('/characters/'.$character->id)
        ->assertOk()
        ->assertSee('Profil Test')
        ->assertSee('Opis postaci.');
});

it('returns 404 for dating characters on public profile', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create([
        'kind' => CharacterKind::Dating,
    ]);

    $this->get('/characters/'.$character->id)->assertNotFound();
});

it('shows official badge on profile', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create([
        'name' => 'Oficjalna Postać',
        'is_official' => true,
    ]);

    $this->get('/characters/'.$character->id)
        ->assertOk()
        ->assertSee('Oficjalna');
});
