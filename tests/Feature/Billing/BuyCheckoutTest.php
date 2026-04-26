<?php

declare(strict_types=1);

use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('rejects unknown package slug with 404', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

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
    $user = UserModel::factory()->unverified()->create();

    $this->actingAs($user)
        ->post('/buy/five')
        ->assertRedirect(route('verification.notice'));
});
