<?php

declare(strict_types=1);

namespace App\Filament\Resources\DatingProfiles\Pages;

use App\Filament\Resources\DatingProfiles\DatingProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDatingProfiles extends ListRecords
{
    protected static string $resource = DatingProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
