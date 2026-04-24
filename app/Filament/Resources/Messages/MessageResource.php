<?php

declare(strict_types=1);

namespace App\Filament\Resources\Messages;

use App\Filament\Resources\Messages\Pages\ListMessages;
use App\Filament\Resources\Messages\Tables\MessagesTable;
use App\Models\Message;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Wiadomości';

    protected static ?string $modelLabel = 'Wiadomość';

    protected static ?string $pluralModelLabel = 'Wiadomości';

    protected static ?int $navigationSort = 40;

    public static function table(Table $table): Table
    {
        return MessagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessages::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
