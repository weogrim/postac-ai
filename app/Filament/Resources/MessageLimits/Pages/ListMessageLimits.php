<?php

declare(strict_types=1);

namespace App\Filament\Resources\MessageLimits\Pages;

use App\Filament\Resources\MessageLimits\MessageLimitResource;
use Filament\Resources\Pages\ListRecords;

class ListMessageLimits extends ListRecords
{
    protected static string $resource = MessageLimitResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
