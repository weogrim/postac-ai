<?php

declare(strict_types=1);

use App\Billing\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('requires auth to see packages', function () {
    /** @var TestCase $this */
    $this->get('/buy')->assertRedirect('/login');
});

it('requires verified email', function () {
    /** @var TestCase $this */
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/buy')->assertRedirect(route('verification.notice'));
});

it('shows all four packages to verified user', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/buy');

    $response->assertOk();

    foreach (Package::cases() as $package) {
        $response->assertSee($package->label());
        $response->assertSee(route('buy.store', $package));
    }
});

it('marks buy nav link active on /buy', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)->get('/buy')->assertSee('Pakiety');
});
