<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('super_admin', 'web');
});

it('redirects guest to admin login', function () {
    /** @var TestCase $this */
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('denies non-admin user access to panel', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

it('allows super_admin into panel', function () {
    /** @var TestCase $this */
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

it('admin resources are registered and reachable', function (string $path) {
    /** @var TestCase $this */
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get($path)
        ->assertOk();
})->with([
    '/admin/characters',
    '/admin/users',
    '/admin/chats',
    '/admin/messages',
    '/admin/message-limits',
    '/admin/manage-chat-settings',
]);
