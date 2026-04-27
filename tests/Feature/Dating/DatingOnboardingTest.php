<?php

declare(strict_types=1);

use App\Legal\Enums\DocumentSlug;
use App\Legal\Models\ConsentModel;
use App\Legal\Models\LegalDocumentModel;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('requires auth for onboarding page', function () {
    /** @var TestCase $this */
    $this->get(route('dating.onboarding'))->assertRedirect(route('login'));
});

it('shows onboarding for authenticated user without consent', function () {
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
        ->get(route('dating.onboarding'))
        ->assertOk()
        ->assertSee('Wchodzę');
});

it('records consent on submit and redirects to dating index', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    $doc = LegalDocumentModel::create([
        'slug' => DocumentSlug::DatingTerms,
        'version' => 1,
        'title' => 'Regulamin Randek',
        'content' => 'Treść',
        'published_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->post(route('dating.onboarding'), ['accepted_dating_terms' => '1'])
        ->assertRedirect(route('dating.index'));

    expect(ConsentModel::query()
        ->where('user_id', $user->id)
        ->where('legal_document_id', $doc->id)
        ->exists()
    )->toBeTrue();
});

it('rejects onboarding submission without checkbox', function () {
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
        ->post(route('dating.onboarding'), [])
        ->assertSessionHasErrors('accepted_dating_terms');
});

it('skips onboarding when consent already exists', function () {
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

    $this->actingAs($user)
        ->get(route('dating.onboarding'))
        ->assertRedirect(route('dating.index'));
});
