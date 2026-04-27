<?php

declare(strict_types=1);

use App\Legal\Enums\DocumentSlug;
use App\Legal\Models\ConsentModel;
use App\Legal\Models\LegalDocumentModel;
use App\User\Models\UserModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(RefreshDatabase::class);

function publishLegalDocuments(): void
{
    LegalDocumentModel::create([
        'slug' => DocumentSlug::Terms,
        'version' => 1,
        'title' => 'Regulamin',
        'content' => 'Treść regulaminu.',
        'published_at' => now()->subDay(),
    ]);

    LegalDocumentModel::create([
        'slug' => DocumentSlug::Privacy,
        'version' => 1,
        'title' => 'Polityka prywatności',
        'content' => 'Treść polityki.',
        'published_at' => now()->subDay(),
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validRegisterPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'alice',
        'email' => 'alice@example.com',
        'password' => 'supersecret123',
        'password_confirmation' => 'supersecret123',
        'birthdate' => Carbon::now()->subYears(20)->toDateString(),
        'accepted_terms' => '1',
        'accepted_privacy' => '1',
    ], $overrides);
}

it('shows the register page', function () {
    /** @var TestCase $this */
    $this->get('/register')->assertOk()->assertSee('Zarejestruj się');
});

it('registers a user and dispatches Registered event', function () {
    /** @var TestCase $this */
    Event::fake();
    publishLegalDocuments();

    $response = $this->post('/register', validRegisterPayload());

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();
    Event::assertDispatched(Registered::class);
    expect(UserModel::where('email', 'alice@example.com')->exists())->toBeTrue();
});

it('rejects duplicate email', function () {
    /** @var TestCase $this */
    UserModel::factory()->create(['email' => 'dup@example.com']);

    $response = $this->post('/register', validRegisterPayload([
        'name' => 'other',
        'email' => 'dup@example.com',
    ]));

    $response->assertSessionHasErrors('email');
});

it('rejects users under 13', function () {
    /** @var TestCase $this */
    $response = $this->post('/register', validRegisterPayload([
        'birthdate' => Carbon::now()->subYears(12)->toDateString(),
    ]));

    $response->assertSessionHasErrors('birthdate');
});

it('requires parental consent for 13-15 year olds', function () {
    /** @var TestCase $this */
    $response = $this->post('/register', validRegisterPayload([
        'birthdate' => Carbon::now()->subYears(14)->toDateString(),
    ]));

    $response->assertSessionHasErrors('accepted_parental');
});

it('accepts 13-15 year olds with parental consent', function () {
    /** @var TestCase $this */
    publishLegalDocuments();

    $response = $this->post('/register', validRegisterPayload([
        'birthdate' => Carbon::now()->subYears(14)->toDateString(),
        'accepted_parental' => '1',
    ]));

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();
});

it('rejects without terms checkbox', function () {
    /** @var TestCase $this */
    $payload = validRegisterPayload();
    unset($payload['accepted_terms']);

    $response = $this->post('/register', $payload);

    $response->assertSessionHasErrors('accepted_terms');
});

it('rejects without privacy checkbox', function () {
    /** @var TestCase $this */
    $payload = validRegisterPayload();
    unset($payload['accepted_privacy']);

    $response = $this->post('/register', $payload);

    $response->assertSessionHasErrors('accepted_privacy');
});

it('records consents linked to latest published documents', function () {
    /** @var TestCase $this */
    publishLegalDocuments();

    $this->post('/register', validRegisterPayload());

    $user = UserModel::where('email', 'alice@example.com')->firstOrFail();

    expect(ConsentModel::where('user_id', $user->id)->count())->toBe(2);
});
