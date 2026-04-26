<?php

declare(strict_types=1);

namespace App\Filament\Resources\MessageLimits;

use App\Chat\Models\MessageLimitModel;
use App\Filament\Resources\MessageLimits\Pages\ListMessageLimits;
use App\Filament\Resources\MessageLimits\Tables\MessageLimitsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MessageLimitResource extends Resource
{
    protected static ?string $model = MessageLimitModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Limity wiadomości';

    protected static ?string $modelLabel = 'Limit wiadomości';

    protected static ?string $pluralModelLabel = 'Limity wiadomości';

    protected static ?int $navigationSort = 50;

    public static function table(Table $table): Table
    {
        return MessageLimitsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessageLimits::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
