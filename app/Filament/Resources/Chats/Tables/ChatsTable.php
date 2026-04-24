<?php

declare(strict_types=1);

namespace App\Filament\Resources\Chats\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ChatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->limit(10)
                    ->copyable(),

                TextColumn::make('user.email')
                    ->label('Użytkownik')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('character.name')
                    ->label('Postać')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('messages_count')
                    ->label('Wiadomości')
                    ->counts('messages')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('deleted_at')
                    ->label('Usunięto')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
