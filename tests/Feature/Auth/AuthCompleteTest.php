<?php

declare(strict_types=1);

use App\Legal\Enums\DocumentSlug;
use App\Legal\Models\ConsentModel;
use App\Legal\Models\LegalDocumentModel;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(RefreshDatabase::class);

function publishConsentDocs(): void
{
    LegalDocumentModel::create([
        'slug' => DocumentSlug::Terms,
        'version' => 1,
        'title' => 'Regulamin',
        'content' => 'Treść.',
        'published_at' => now()->subDay(),
    ]);

    LegalDocumentModel::create([
        'slug' => DocumentSlug::Privacy,
        'version' => 1,
        'title' => 'Polityka prywatności',
        'content' => 'Treść.',
        'published_at' => now()->subDay(),
    ]);
}

it('shows the completion form for users without birthdate', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['birthdate' => null]);

    $this->actingAs($user)
        ->get('/onboarding')
        ->assertOk()
        ->assertSee('Dokończ rejestrację');
});

it('redirects to home when user already has birthdate', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['birthdate' => now()->subYears(20)]);

    $this->actingAs($user)
        ->get('/onboarding')
        ->assertRedirect(route('home'));
});

it('saves birthdate and consents on POST', function () {
    /** @var TestCase $this */
    publishConsentDocs();
    $user = UserModel::factory()->create(['birthdate' => null]);

    $response = $this->actingAs($user)->post('/onboarding', [
        'birthdate' => Carbon::now()->subYears(25)->toDateString(),
        'accepted_terms' => '1',
        'accepted_privacy' => '1',
    ]);

    $response->assertRedirect(route('home'));
    expect($user->fresh()->birthdate)->not->toBeNull();
    expect(ConsentModel::where('user_id', $user->id)->count())->toBe(2);
});

it('rejects under-13 on completion', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['birthdate' => null]);

    $response = $this->actingAs($user)->post('/onboarding', [
        'birthdate' => Carbon::now()->subYears(11)->toDateString(),
        'accepted_terms' => '1',
        'accepted_privacy' => '1',
    ]);

    $response->assertSessionHasErrors('birthdate');
});

it('requires parental consent for 13-15 on completion', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['birthdate' => null]);

    $response = $this->actingAs($user)->post('/onboarding', [
        'birthdate' => Carbon::now()->subYears(14)->toDateString(),
        'accepted_terms' => '1',
        'accepted_privacy' => '1',
    ]);

    $response->assertSessionHasErrors('accepted_parental');
});

it('requires both consent checkboxes', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['birthdate' => null]);

    $response = $this->actingAs($user)->post('/onboarding', [
        'birthdate' => Carbon::now()->subYears(20)->toDateString(),
        'accepted_terms' => '1',
    ]);

    $response->assertSessionHasErrors('accepted_privacy');
});

it('blocks guests', function () {
    /** @var TestCase $this */
    $this->get('/onboarding')->assertRedirect(route('login'));
});
