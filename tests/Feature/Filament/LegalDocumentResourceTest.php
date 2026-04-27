<?php

declare(strict_types=1);

use App\Filament\Resources\LegalDocuments\Pages\CreateLegalDocument;
use App\Filament\Resources\LegalDocuments\Pages\EditLegalDocument;
use App\Filament\Resources\LegalDocuments\Pages\ListLegalDocuments;
use App\Legal\Enums\DocumentSlug;
use App\Legal\Models\LegalDocumentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('lists existing legal documents', function () {
    /** @var TestCase $this */
    loginAsAdmin();

    $document = LegalDocumentModel::create([
        'slug' => DocumentSlug::Terms,
        'version' => 1,
        'title' => 'Regulamin v1',
        'content' => 'Treść.',
        'published_at' => now()->subDay(),
    ]);

    Livewire::test(ListLegalDocuments::class)
        ->assertCanSeeTableRecords([$document]);
});

it('creates a legal document via Filament form', function () {
    /** @var TestCase $this */
    loginAsAdmin();

    Livewire::test(CreateLegalDocument::class)
        ->fillForm([
            'slug' => DocumentSlug::Terms->value,
            'version' => 1,
            'title' => 'Pierwsza wersja regulaminu',
            'content' => 'Treść regulaminu w **markdown**.',
            'published_at' => now()->subHour(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(LegalDocumentModel::where('title', 'Pierwsza wersja regulaminu')->exists())->toBeTrue();
});

it('edits an existing legal document', function () {
    /** @var TestCase $this */
    loginAsAdmin();

    $document = LegalDocumentModel::create([
        'slug' => DocumentSlug::Privacy,
        'version' => 1,
        'title' => 'Stary tytuł',
        'content' => 'Treść.',
        'published_at' => null,
    ]);

    Livewire::test(EditLegalDocument::class, ['record' => $document->id])
        ->fillForm(['title' => 'Nowy tytuł'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($document->fresh()->title)->toBe('Nowy tytuł');
});

it('duplicates a document as a new draft version', function () {
    /** @var TestCase $this */
    loginAsAdmin();

    $original = LegalDocumentModel::create([
        'slug' => DocumentSlug::Terms,
        'version' => 1,
        'title' => 'Regulamin v1',
        'content' => 'Stara treść.',
        'published_at' => now()->subDay(),
    ]);

    Livewire::test(ListLegalDocuments::class)
        ->callTableAction('duplicate', $original);

    $newest = LegalDocumentModel::query()
        ->where('slug', DocumentSlug::Terms)
        ->orderByDesc('version')
        ->first();

    expect($newest->version)->toBe(2);
    expect($newest->published_at)->toBeNull();
    expect($newest->content)->toBe('Stara treść.');
});
