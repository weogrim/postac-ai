<?php

declare(strict_types=1);

namespace App\Filament\Resources\Chats;

use App\Filament\Resources\Chats\Pages\ListChats;
use App\Filament\Resources\Chats\Pages\ViewChat;
use App\Filament\Resources\Chats\Tables\ChatsTable;
use App\Models\Chat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChatResource extends Resource
{
    protected static ?string $model = Chat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Czaty';

    protected static ?string $modelLabel = 'Czat';

    protected static ?string $pluralModelLabel = 'Czaty';

    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return ChatsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChats::route('/'),
            'view' => ViewChat::route('/{record}'),
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
