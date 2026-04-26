<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Character\Models\CharacterModel;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageModel;
use App\User\Models\UserModel;
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

    private function seedAdmin(): UserModel
    {
        $email = trim((string) config('auth.admin_email')) ?: 'admin@postac.ai';

        $admin = UserModel::firstOrCreate(
            ['email' => $email],
            ['name' => 'Admin', 'password' => bcrypt('password'), 'email_verified_at' => now()]
        );

        if (! $admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        return $admin;
    }

    private function seedSampleData(UserModel $admin): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        $users = UserModel::factory(4)->create()->push($admin);
        $characters = CharacterModel::factory(8)->withAvatar()->recycle($users)->create();

        $users->take(3)->each(function (UserModel $user) use ($characters) {
            $characters->random(2)->each(function (CharacterModel $character) use ($user) {
                $chat = ChatModel::factory()->create([
                    'user_id' => $user->id,
                    'character_id' => $character->id,
                ]);
                MessageModel::factory()->fromUser($chat)->create(['content' => 'Cześć!']);
                MessageModel::factory()->fromCharacter($chat)->create(['content' => 'Witaj.']);
            });
        });
    }
}
