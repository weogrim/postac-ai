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

it('creates a user on first Google callback and logs them in', function () {
    /** @var TestCase $this */
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getEmail')->andReturn('new@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Nowy Użytkownik');
    $socialiteUser->shouldReceive('getId')->andReturn('google-123');

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('home'));
    $user = UserModel::where('email', 'new@example.com')->firstOrFail();
    expect($user->password)->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('logs in existing user without overwriting password', function () {
    /** @var TestCase $this */
    $existing = UserModel::factory()->create([
        'email' => 'existing@example.com',
        'password' => bcrypt('keep-this'),
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
