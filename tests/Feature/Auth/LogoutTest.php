<?php

declare(strict_types=1);

use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('logs out an authenticated user', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect(route('home'));
    $this->assertGuest();
});
