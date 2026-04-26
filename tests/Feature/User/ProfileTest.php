<?php

declare(strict_types=1);

use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('shows the profile page', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    $this->actingAs($user)->get('/me')
        ->assertOk()
        ->assertSee($user->name)
        ->assertSee($user->email);
});

it('updates name and email', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['email' => 'old@example.com']);

    $response = $this->actingAs($user)->patch('/me', [
        'name' => 'Nowa Nazwa',
        'email' => 'new@example.com',
    ]);

    $response->assertRedirect();
    $fresh = $user->fresh();
    expect($fresh->name)->toBe('Nowa Nazwa');
    expect($fresh->email)->toBe('new@example.com');
    expect($fresh->email_verified_at)->toBeNull();
});

it('keeps email_verified_at when email unchanged', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['email' => 'same@example.com']);
    /** @var Carbon $verifiedAt */
    $verifiedAt = $user->email_verified_at;

    $this->actingAs($user)->patch('/me', [
        'name' => 'Nowa',
        'email' => 'same@example.com',
    ]);

    /** @var Carbon $freshVerifiedAt */
    $freshVerifiedAt = $user->fresh()->email_verified_at;
    expect($freshVerifiedAt->timestamp)->toBe($verifiedAt->timestamp);
});

it('updates password with current password check', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['password' => Hash::make('old-pass-123')]);

    $response = $this->actingAs($user)->patch('/me/password', [
        'current_password' => 'old-pass-123',
        'password' => 'new-pass-123',
        'password_confirmation' => 'new-pass-123',
    ]);

    $response->assertRedirect();
    expect(Hash::check('new-pass-123', $user->fresh()->password))->toBeTrue();
});

it('rejects password update with wrong current password', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['password' => Hash::make('old-pass-123')]);

    $response = $this->actingAs($user)->patch('/me/password', [
        'current_password' => 'wrong',
        'password' => 'new-pass-123',
        'password_confirmation' => 'new-pass-123',
    ]);

    $response->assertSessionHasErrors('current_password');
});

it('allows OAuth user (null password) to set password without current', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['password' => null]);

    $response = $this->actingAs($user)->patch('/me/password', [
        'password' => 'brand-new-123',
        'password_confirmation' => 'brand-new-123',
    ]);

    $response->assertRedirect();
    expect(Hash::check('brand-new-123', $user->fresh()->password))->toBeTrue();
});

it('deletes account with USUŃ confirmation', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    $response = $this->actingAs($user)->delete('/me', ['confirm' => 'USUŃ']);

    $response->assertRedirect(route('home'));
    $this->assertGuest();
    expect(UserModel::find($user->id))->toBeNull();
});

it('rejects delete without correct confirmation', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    $response = $this->actingAs($user)->delete('/me', ['confirm' => 'nope']);

    $response->assertSessionHasErrors('confirm');
    expect(UserModel::find($user->id))->not->toBeNull();
});
