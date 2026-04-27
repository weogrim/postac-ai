<?php

declare(strict_types=1);

use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit');
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

function loginAsAdmin(): UserModel
{
    Role::findOrCreate('super_admin', 'web');

    $admin = UserModel::factory()->create();
    $admin->assignRole('super_admin');
    auth()->login($admin);

    return $admin;
}
