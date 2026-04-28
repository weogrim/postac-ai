<?php

declare(strict_types=1);

use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('shows zaloguj/zarejestruj for anonymous visitor', function () {
    /** @var TestCase $this */
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Zaloguj');
    $response->assertSee('Zarejestruj');
    $response->assertDontSee('Profil');
    $response->assertDontSee('Pakiety');
});

it('shows zaloguj/zarejestruj for ghost user (email null)', function () {
    /** @var TestCase $this */
    $ghost = UserModel::factory()->create([
        'email' => null,
        'password' => null,
        'birthdate' => null,
        'email_verified_at' => null,
    ]);

    auth()->login($ghost);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Zaloguj');
    $response->assertSee('Zarejestruj');
    $response->assertDontSee('Pakiety');
    $response->assertDontSee('Moje limity');
    $response->assertDontSee('Kup wiadomości');
    $response->assertDontSee('Wyloguj');
});

it('shows avatar dropdown and pakiety for registered user', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create([
        'email' => 'jane@example.com',
    ]);

    auth()->login($user);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Pakiety');
    $response->assertSee('Profil');
    $response->assertSee('Moje limity');
    $response->assertSee('Wyloguj');
    $response->assertDontSee('>Zaloguj<', false);
    $response->assertDontSee('>Zarejestruj<', false);
});
