<?php

declare(strict_types=1);

namespace App\Filament\Resources\Consents;

use App\Filament\Resources\Consents\Pages\ListConsents;
use App\Filament\Resources\Consents\Tables\ConsentsTable;
use App\Legal\Models\ConsentModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConsentResource extends Resource
{
    protected static ?string $model = ConsentModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Zgody użytkowników';

    protected static ?string $modelLabel = 'Zgoda';

    protected static ?string $pluralModelLabel = 'Zgody';

    protected static ?int $navigationSort = 81;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return ConsentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsents::route('/'),
        ];
    }
}
