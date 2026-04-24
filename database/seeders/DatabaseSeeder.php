<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Character;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function () {
            $this->seedRoles();
            $admin = $this->seedAdmin();
            $this->seedSampleData($admin);
        });
    }

    private function seedRoles(): void
    {
        Role::findOrCreate('super_admin', 'web');
    }

    private function seedAdmin(): User
    {
        $email = trim((string) config('auth.admin_email')) ?: 'admin@postac.ai';

        $admin = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Admin', 'password' => bcrypt('password'), 'email_verified_at' => now()]
        );

        if (! $admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        return $admin;
    }

    private function seedSampleData(User $admin): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        $users = User::factory(4)->create()->push($admin);
        $characters = Character::factory(8)->withAvatar()->recycle($users)->create();

        $users->take(3)->each(function (User $user) use ($characters) {
            $characters->random(2)->each(function (Character $character) use ($user) {
                $chat = Chat::factory()->create([
                    'user_id' => $user->id,
                    'character_id' => $character->id,
                ]);
                Message::factory()->fromUser($chat)->create(['content' => 'Cześć!']);
                Message::factory()->fromCharacter($chat)->create(['content' => 'Witaj.']);
            });
        });
    }
}
