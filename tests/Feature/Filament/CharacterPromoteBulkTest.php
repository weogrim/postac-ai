<?php

declare(strict_types=1);

use App\Character\Models\CharacterModel;
use App\Filament\Resources\Characters\Pages\ListCharacters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('promotes selected characters via bulk action', function () {
    /** @var TestCase $this */
    loginAsAdmin();

    $a = CharacterModel::factory()->create(['is_official' => false]);
    $b = CharacterModel::factory()->create(['is_official' => false]);
    $c = CharacterModel::factory()->create(['is_official' => false]);

    Livewire::test(ListCharacters::class)
        ->callTableBulkAction('promote', [$a->id, $b->id]);

    expect($a->refresh()->is_official)->toBeTrue();
    expect($b->refresh()->is_official)->toBeTrue();
    expect($c->refresh()->is_official)->toBeFalse();
});

it('unpromotes selected characters via bulk action', function () {
    /** @var TestCase $this */
    loginAsAdmin();

    $a = CharacterModel::factory()->create(['is_official' => true]);
    $b = CharacterModel::factory()->create(['is_official' => true]);

    Livewire::test(ListCharacters::class)
        ->callTableBulkAction('unpromote', [$a->id, $b->id]);

    expect($a->refresh()->is_official)->toBeFalse();
    expect($b->refresh()->is_official)->toBeFalse();
});
