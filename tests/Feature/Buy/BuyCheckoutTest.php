<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('rejects unknown package slug with 404', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/buy/nonexistent')
        ->assertNotFound();
});

it('requires auth to checkout', function () {
    /** @var TestCase $this */
    $this->post('/buy/five')->assertRedirect('/login');
});

it('requires verified email to checkout', function () {
    /** @var TestCase $this */
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post('/buy/five')
        ->assertRedirect(route('verification.notice'));
});
