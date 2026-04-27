<?php

declare(strict_types=1);

use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('redirects to Google on known provider', function () {
    /** @var TestCase $this */
    $response = $this->get('/auth/google');
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('google.com');
});

it('returns 404 on unknown provider', function () {
    /** @var TestCase $this */
    $this->get('/auth/twitter')->assertNotFound();
});

it('creates a user on first Google callback and redirects to completion', function () {
    /** @var TestCase $this */
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getEmail')->andReturn('new@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Nowy Użytkownik');
    $socialiteUser->shouldReceive('getId')->andReturn('google-123');

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('auth.complete'));
    $user = UserModel::where('email', 'new@example.com')->firstOrFail();
    expect($user->password)->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->birthdate)->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('logs in existing user with birthdate straight to home', function () {
    /** @var TestCase $this */
    $existing = UserModel::factory()->create([
        'email' => 'existing@example.com',
        'password' => bcrypt('keep-this'),
        'birthdate' => now()->subYears(20),
    ]);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Existing');
    $socialiteUser->shouldReceive('getId')->andReturn('google-456');

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($existing->fresh());
    expect($existing->fresh()->password)->toBe($existing->password);
});

it('redirects existing user without birthdate to completion', function () {
    /** @var TestCase $this */
    $existing = UserModel::factory()->create([
        'email' => 'partial@example.com',
        'birthdate' => null,
    ]);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getEmail')->andReturn('partial@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Partial');
    $socialiteUser->shouldReceive('getId')->andReturn('google-789');

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('auth.complete'));
    $this->assertAuthenticatedAs($existing->fresh());
});

it('upgrades a ghost user to OAuth when email is free', function () {
    /** @var TestCase $this */
    $ghost = UserModel::factory()->create([
        'email' => null,
        'password' => null,
        'birthdate' => null,
        'email_verified_at' => null,
        'name' => 'Gość',
    ]);

    $this->actingAs($ghost);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getEmail')->andReturn('ghost-up@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Ghost Upgraded');
    $socialiteUser->shouldReceive('getId')->andReturn('google-ghost-1');

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('auth.complete'));

    $upgraded = UserModel::query()->find($ghost->id);
    expect($upgraded->email)->toBe('ghost-up@example.com');
    expect($upgraded->isGuest())->toBeFalse();
    expect($upgraded->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($upgraded);
});

it('OAuth conflict: existing email kicks ghost and logs into existing account', function () {
    /** @var TestCase $this */
    $existing = UserModel::factory()->create([
        'email' => 'real@example.com',
        'birthdate' => now()->subYears(20),
    ]);

    $ghost = UserModel::factory()->create([
        'email' => null,
        'password' => null,
        'birthdate' => null,
        'email_verified_at' => null,
        'name' => 'Gość',
    ]);

    $this->actingAs($ghost);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getEmail')->andReturn('real@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Real');
    $socialiteUser->shouldReceive('getId')->andReturn('google-conflict');

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('home'));

    expect(UserModel::query()->find($ghost->id))->toBeNull();
    $this->assertAuthenticatedAs($existing->fresh());
});
