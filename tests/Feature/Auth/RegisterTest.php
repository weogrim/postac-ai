<?php

declare(strict_types=1);

use App\User\Models\UserModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('shows the register page', function () {
    /** @var TestCase $this */
    $this->get('/register')->assertOk()->assertSee('Zarejestruj się');
});

it('registers a user and dispatches Registered event', function () {
    /** @var TestCase $this */
    Event::fake();

    $response = $this->post('/register', [
        'name' => 'alice',
        'email' => 'alice@example.com',
        'password' => 'supersecret123',
        'password_confirmation' => 'supersecret123',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();
    Event::assertDispatched(Registered::class);
    expect(UserModel::where('email', 'alice@example.com')->exists())->toBeTrue();
});

it('rejects duplicate email', function () {
    /** @var TestCase $this */
    UserModel::factory()->create(['email' => 'dup@example.com']);

    $response = $this->post('/register', [
        'name' => 'other',
        'email' => 'dup@example.com',
        'password' => 'supersecret123',
        'password_confirmation' => 'supersecret123',
    ]);

    $response->assertSessionHasErrors('email');
});
