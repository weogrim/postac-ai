<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Imię')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                IconColumn::make('email_verified_at')
                    ->label('Zweryfikowany')
                    ->boolean(),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge(),

                TextColumn::make('characters_count')
                    ->label('Postacie')
                    ->counts('characters')
                    ->sortable(),

                TextColumn::make('chats_count')
                    ->label('Czaty')
                    ->counts('chats')
                    ->sortable(),

                TextColumn::make('stripe_id')
                    ->label('Stripe')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dołączył')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Rola')
                    ->relationship('roles', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
