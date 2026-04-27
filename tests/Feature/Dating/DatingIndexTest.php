<?php

declare(strict_types=1);

use App\Character\Enums\CharacterKind;
use App\Character\Models\CharacterModel;
use App\Dating\Models\DatingProfileModel;
use App\Legal\Enums\DocumentSlug;
use App\Legal\Models\ConsentModel;
use App\Legal\Models\LegalDocumentModel;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('lists dating profiles for guest visitor', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create([
        'kind' => CharacterKind::Dating,
        'is_official' => true,
        'name' => 'Maja',
    ]);
    DatingProfileModel::factory()->create(['character_id' => $character->id, 'city' => 'Kraków']);

    $this->get(route('dating.index'))
        ->assertOk()
        ->assertSee('Maja')
        ->assertSee('Kraków');
});

it('skips dating characters without dating profile', function () {
    /** @var TestCase $this */
    CharacterModel::factory()->create([
        'kind' => CharacterKind::Dating,
        'is_official' => true,
        'name' => 'OrphanDating',
    ]);

    $this->get(route('dating.index'))
        ->assertOk()
        ->assertDontSee('OrphanDating');
});

it('redirects authenticated user without dating consent to onboarding', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    LegalDocumentModel::create([
        'slug' => DocumentSlug::DatingTerms,
        'version' => 1,
        'title' => 'Regulamin Randek',
        'content' => 'Treść',
        'published_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('dating.index'))
        ->assertRedirect(route('dating.onboarding'));
});

it('lets authenticated user with consent see dating index', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    $doc = LegalDocumentModel::create([
        'slug' => DocumentSlug::DatingTerms,
        'version' => 1,
        'title' => 'Regulamin Randek',
        'content' => 'Treść',
        'published_at' => now()->subDay(),
    ]);

    ConsentModel::create([
        'user_id' => $user->id,
        'legal_document_id' => $doc->id,
        'accepted_at' => now(),
    ]);

    $character = CharacterModel::factory()->create(['kind' => CharacterKind::Dating, 'is_official' => true]);
    DatingProfileModel::factory()->create(['character_id' => $character->id]);

    $this->actingAs($user)
        ->get(route('dating.index'))
        ->assertOk();
});

it('returns 404 when showing regular character via dating route', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create(['kind' => CharacterKind::Regular]);

    $this->get(route('dating.show', $character))->assertNotFound();
});

it('shows dating profile detail with bio and interests', function () {
    /** @var TestCase $this */
    $character = CharacterModel::factory()->create([
        'kind' => CharacterKind::Dating,
        'is_official' => true,
        'name' => 'Ola',
    ]);
    DatingProfileModel::factory()->create([
        'character_id' => $character->id,
        'age' => 27,
        'city' => 'Warszawa',
        'bio' => 'Lubię długie spacery i kawę.',
        'interests' => ['kawa', 'kino'],
    ]);

    $this->get(route('dating.show', $character))
        ->assertOk()
        ->assertSee('Ola')
        ->assertSee('27')
        ->assertSee('Warszawa')
        ->assertSee('Lubię długie spacery')
        ->assertSee('kawa')
        ->assertSee('kino');
});
