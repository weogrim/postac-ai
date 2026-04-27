<?php

declare(strict_types=1);

use App\Legal\Enums\DocumentSlug;
use App\Legal\Models\LegalDocumentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('renders latest published version of a document', function () {
    /** @var TestCase $this */
    LegalDocumentModel::create([
        'slug' => DocumentSlug::Terms,
        'version' => 1,
        'title' => 'Regulamin v1',
        'content' => 'Stara wersja.',
        'published_at' => now()->subDays(10),
    ]);

    LegalDocumentModel::create([
        'slug' => DocumentSlug::Terms,
        'version' => 2,
        'title' => 'Regulamin v2',
        'content' => '## Nowy nagłówek',
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/legal/terms');

    $response->assertOk()
        ->assertSee('Regulamin v2')
        ->assertSee('<h2>Nowy nagłówek</h2>', escape: false)
        ->assertDontSee('Stara wersja.');
});

it('skips unpublished versions', function () {
    /** @var TestCase $this */
    LegalDocumentModel::create([
        'slug' => DocumentSlug::Privacy,
        'version' => 1,
        'title' => 'Polityka v1',
        'content' => 'Pierwsza opublikowana.',
        'published_at' => now()->subDay(),
    ]);

    LegalDocumentModel::create([
        'slug' => DocumentSlug::Privacy,
        'version' => 2,
        'title' => 'Polityka v2 draft',
        'content' => 'Wersja robocza.',
        'published_at' => null,
    ]);

    $response = $this->get('/legal/privacy');

    $response->assertOk()
        ->assertSee('Polityka v1')
        ->assertDontSee('Polityka v2 draft');
});

it('returns 404 when no published version exists', function () {
    /** @var TestCase $this */
    LegalDocumentModel::create([
        'slug' => DocumentSlug::Terms,
        'version' => 1,
        'title' => 'Draft',
        'content' => 'Draft.',
        'published_at' => null,
    ]);

    $this->get('/legal/terms')->assertNotFound();
});

it('returns 404 for unknown slug', function () {
    /** @var TestCase $this */
    $this->get('/legal/unknown-slug')->assertNotFound();
});

it('escapes raw HTML in markdown content', function () {
    /** @var TestCase $this */
    LegalDocumentModel::create([
        'slug' => DocumentSlug::Terms,
        'version' => 1,
        'title' => 'Regulamin',
        'content' => '<script>alert("xss")</script> Treść.',
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/legal/terms');

    $response->assertOk()
        ->assertDontSee('<script>alert', escape: false);
});
