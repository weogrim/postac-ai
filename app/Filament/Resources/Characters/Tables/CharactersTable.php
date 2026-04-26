<?php

declare(strict_types=1);

namespace App\Filament\Resources\Characters\Tables;

use App\Character\Models\CharacterModel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CharactersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->getStateUsing(fn (CharacterModel $record): string => $record->avatarUrl('thumb')),

                TextColumn::make('name')
                    ->label('Nazwa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('author.name')
                    ->label('Autor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('chats_count')
                    ->label('Czaty')
                    ->counts('chats')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('deleted_at')
                    ->label('Usunięto')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
