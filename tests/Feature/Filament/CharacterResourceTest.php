<?php

declare(strict_types=1);

use App\Character\Models\CharacterModel;
use App\Filament\Resources\Characters\Pages\CreateCharacter;
use App\Filament\Resources\Characters\Pages\EditCharacter;
use App\Filament\Resources\Characters\Pages\ListCharacters;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('super_admin', 'web');
});

function loginAsAdmin(): UserModel
{
    $admin = UserModel::factory()->create();
    $admin->assignRole('super_admin');
    auth()->login($admin);

    return $admin;
}

it('lists existing characters', function () {
    /** @var TestCase $this */
    $admin = loginAsAdmin();
    $characters = CharacterModel::factory(3)->recycle($admin)->create();

    Livewire::test(ListCharacters::class)
        ->assertCanSeeTableRecords($characters);
});

it('creates a character through Filament form', function () {
    /** @var TestCase $this */
    $admin = loginAsAdmin();

    Livewire::test(CreateCharacter::class)
        ->fillForm([
            'name' => 'Admin-stworzona postać',
            'prompt' => 'Jesteś postacią stworzoną przez administratora. '.str_repeat('tekst ', 10),
            'user_id' => $admin->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(CharacterModel::query()->where('name', 'Admin-stworzona postać')->exists())->toBeTrue();
});

it('edits a character through Filament form', function () {
    /** @var TestCase $this */
    $admin = loginAsAdmin();
    $character = CharacterModel::factory()->recycle($admin)->create(['name' => 'Stara nazwa']);

    Livewire::test(EditCharacter::class, ['record' => $character->getKey()])
        ->fillForm(['name' => 'Nowa nazwa'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($character->fresh()->name)->toBe('Nowa nazwa');
});

it('soft-deletes a character through Filament delete action', function () {
    /** @var TestCase $this */
    $admin = loginAsAdmin();
    $character = CharacterModel::factory()->recycle($admin)->create();

    Livewire::test(EditCharacter::class, ['record' => $character->getKey()])
        ->callAction('delete');

    expect($character->fresh()->trashed())->toBeTrue();
});
