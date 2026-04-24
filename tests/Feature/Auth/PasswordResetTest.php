<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('sends a password reset link', function () {
    /** @var TestCase $this */
    Notification::fake();
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->post('/forgot-password', ['email' => 'jane@example.com']);

    $response->assertSessionHasNoErrors();
    Notification::assertSentTo($user, ResetPassword::class);
});

it('resets the password with a valid token', function () {
    /** @var TestCase $this */
    Notification::fake();
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => Hash::make('old-password'),
    ]);

    $this->post('/forgot-password', ['email' => 'jane@example.com']);

    /** @var string|null $token */
    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'jane@example.com',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertRedirect(route('login'));
    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});
