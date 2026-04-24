<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('404s for users without a stripe id', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('billing.portal'))->assertNotFound();
});

it('redirects guests to login', function () {
    /** @var TestCase $this */
    $this->get(route('billing.portal'))->assertRedirect(route('login'));
});

it('shows billing portal link only when user has stripe id', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('profile.show'))->assertDontSee('Faktury i płatności');

    $user->stripe_id = 'cus_test_fake';
    $user->save();

    $this->actingAs($user->fresh())->get(route('profile.show'))->assertSee('Faktury i płatności');
});
