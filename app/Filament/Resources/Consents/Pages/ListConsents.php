<?php

declare(strict_types=1);

namespace App\Filament\Resources\Consents\Pages;

use App\Filament\Resources\Consents\ConsentResource;
use Filament\Resources\Pages\ListRecords;

class ListConsents extends ListRecords
{
    protected static string $resource = ConsentResource::class;
}
