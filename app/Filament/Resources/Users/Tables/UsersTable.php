<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\User\Models\UserModel;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('account_type')
                    ->label('Typ')
                    ->badge()
                    ->state(fn (UserModel $record): string => match (true) {
                        $record->isGuest() => 'gość',
                        $record->email_verified_at === null => 'niezweryfikowany',
                        default => 'zweryfikowany',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'gość' => 'gray',
                        'niezweryfikowany' => 'warning',
                        default => 'success',
                    }),

                IconColumn::make('email_verified_at')
                    ->label('Zweryfikowany')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                SelectFilter::make('account_type')
                    ->label('Typ konta')
                    ->options([
                        'guest' => 'Gość',
                        'unverified' => 'Niezweryfikowany',
                        'verified' => 'Zweryfikowany',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'guest' => $query->whereNull('email'),
                            'unverified' => $query->whereNotNull('email')->whereNull('email_verified_at'),
                            'verified' => $query->whereNotNull('email_verified_at'),
                            default => $query,
                        };
                    }),

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
