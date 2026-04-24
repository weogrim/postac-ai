<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('john@example.com|127.0.0.1');
});

it('shows the login page', function () {
    /** @var TestCase $this */
    $this->get('/login')->assertOk()->assertSee('Zaloguj się');
});

it('logs in with valid credentials', function () {
    /** @var TestCase $this */
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('secret1234'),
    ]);

    $response = $this->post('/login', [
        'email' => 'john@example.com',
        'password' => 'secret1234',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

it('rejects wrong password', function () {
    /** @var TestCase $this */
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('secret1234'),
    ]);

    $response = $this->post('/login', [
        'email' => 'john@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('throttles after 5 failed attempts', function () {
    /** @var TestCase $this */
    Event::fake();
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('secret1234'),
    ]);

    foreach (range(1, 5) as $_) {
        $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'wrong',
        ]);
    }

    $response = $this->post('/login', [
        'email' => 'john@example.com',
        'password' => 'secret1234',
    ]);

    $response->assertSessionHasErrors('email');
    Event::assertDispatched(Lockout::class);
});
