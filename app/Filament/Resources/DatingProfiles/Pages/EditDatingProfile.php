<?php

declare(strict_types=1);

namespace App\Filament\Resources\DatingProfiles\Pages;

use App\Dating\Models\DatingProfileModel;
use App\Filament\Resources\DatingProfiles\DatingProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditDatingProfile extends EditRecord
{
    protected static string $resource = DatingProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var DatingProfileModel $record */
        $record = $this->getRecord();
        $character = $record->character;

        if ($character !== null) {
            $data['character_name'] = $character->name;
            $data['character_description'] = $character->description;
            $data['character_greeting'] = $character->greeting;
            $data['character_prompt'] = $character->prompt;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var DatingProfileModel $record */
        return DB::transaction(function () use ($record, $data): DatingProfileModel {
            $character = $record->character;
            if ($character !== null) {
                $character->update([
                    'name' => $data['character_name'],
                    'description' => $data['character_description'] ?? null,
                    'greeting' => $data['character_greeting'] ?? null,
                    'prompt' => $data['character_prompt'],
                ]);
            }

            $record->update([
                'age' => (int) $data['age'],
                'city' => $data['city'],
                'bio' => $data['bio'],
                'interests' => $data['interests'] ?? [],
                'accent_color' => $data['accent_color'] ?? null,
            ]);

            return $record;
        });
    }
}
