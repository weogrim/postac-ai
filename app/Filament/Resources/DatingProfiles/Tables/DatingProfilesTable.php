<?php

declare(strict_types=1);

namespace App\Filament\Resources\DatingProfiles\Tables;

use App\Dating\Models\DatingProfileModel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DatingProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->getStateUsing(fn (DatingProfileModel $record): string => $record->character?->avatarUrl('thumb') ?? ''),

                TextColumn::make('character.name')
                    ->label('Imię')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('age')
                    ->label('Wiek')
                    ->sortable(),

                TextColumn::make('city')
                    ->label('Miasto')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
