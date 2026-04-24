<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('renders the verification notice for unverified user', function () {
    /** @var TestCase $this */
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/verify-email')
        ->assertOk()
        ->assertSee('Potwierdź');
});

it('redirects verified user away from notice', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)->get('/verify-email')
        ->assertRedirect(route('home'));
});

it('verifies email via signed link and dispatches Verified event', function () {
    /** @var TestCase $this */
    Event::fake();
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->actingAs($user)->get($url);

    $response->assertRedirect();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('blocks verified-guarded routes for unverified users', function () {
    /** @var TestCase $this */
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('profile.show'))
        ->assertRedirect(route('verification.notice'));
});
