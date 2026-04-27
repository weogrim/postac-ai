<?php

declare(strict_types=1);

namespace App\Filament\Resources\DatingProfiles\Pages;

use App\Character\Enums\CharacterKind;
use App\Character\Models\CharacterModel;
use App\Dating\Models\DatingProfileModel;
use App\Filament\Resources\DatingProfiles\DatingProfileResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateDatingProfile extends CreateRecord
{
    protected static string $resource = DatingProfileResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): DatingProfileModel {
            $character = CharacterModel::create([
                'user_id' => $this->getAdminUserId(),
                'name' => $data['character_name'],
                'description' => $data['character_description'] ?? null,
                'greeting' => $data['character_greeting'] ?? null,
                'prompt' => $data['character_prompt'],
                'kind' => CharacterKind::Dating,
                'is_official' => true,
            ]);

            return DatingProfileModel::create([
                'character_id' => $character->id,
                'age' => (int) $data['age'],
                'city' => $data['city'],
                'bio' => $data['bio'],
                'interests' => $data['interests'] ?? [],
                'accent_color' => $data['accent_color'] ?? null,
            ]);
        });
    }

    private function getAdminUserId(): int
    {
        $user = auth()->user();

        if ($user === null) {
            throw new RuntimeException('Admin user not authenticated.');
        }

        return (int) $user->getAuthIdentifier();
    }
}
