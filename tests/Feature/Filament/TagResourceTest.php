<?php

declare(strict_types=1);

use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Tags\Pages\EditTag;
use App\Filament\Resources\Tags\Pages\ListTags;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Tags\Tag;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('creates a category tag through Filament form', function () {
    /** @var TestCase $this */
    loginAsAdmin();

    Livewire::test(CreateTag::class)
        ->fillForm([
            'name' => 'Historia',
            'type' => 'category',
            'order_column' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Tag::query()->where('type', 'category')->count())->toBe(1);
});

it('creates a free tag', function () {
    /** @var TestCase $this */
    loginAsAdmin();

    Livewire::test(CreateTag::class)
        ->fillForm([
            'name' => 'Filozof',
            'type' => 'tag',
            'order_column' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Tag::query()->where('type', 'tag')->count())->toBe(1);
});

it('lists existing tags', function () {
    /** @var TestCase $this */
    loginAsAdmin();

    Tag::findOrCreateFromString('Anime', 'category');
    Tag::findOrCreateFromString('Gry', 'category');

    Livewire::test(ListTags::class)
        ->assertCanSeeTableRecords(Tag::all());
});

it('edits a tag', function () {
    /** @var TestCase $this */
    loginAsAdmin();

    $tag = Tag::findOrCreateFromString('Stara nazwa', 'tag');

    Livewire::test(EditTag::class, ['record' => $tag->getKey()])
        ->fillForm(['name' => 'Nowa nazwa'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tag->fresh()->name)->toBe('Nowa nazwa');
});
