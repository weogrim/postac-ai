<?php

declare(strict_types=1);

namespace App\Filament\Resources\MessageLimits\Tables;

use App\Chat\Enums\LimitType;
use App\Chat\Enums\ModelType;
use App\Chat\Models\MessageLimitModel;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MessageLimitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')
                    ->label('Użytkownik')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('limit_type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn (LimitType $state): string => $state->value),

                TextColumn::make('model_type')
                    ->label('Model')
                    ->badge()
                    ->formatStateUsing(fn (ModelType $state): string => $state->value),

                TextColumn::make('priority')
                    ->label('Priorytet')
                    ->sortable(),

                TextColumn::make('used')
                    ->label('Użyto')
                    ->getStateUsing(fn (MessageLimitModel $record): string => "{$record->used} / {$record->quota}")
                    ->sortable(),

                TextColumn::make('period_start')
                    ->label('Okno od')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('limit_type')
                    ->label('Typ limitu')
                    ->options([
                        LimitType::Daily->value => 'Daily',
                        LimitType::Package->value => 'Package',
                    ]),

                SelectFilter::make('model_type')
                    ->label('Model')
                    ->options([
                        ModelType::Gpt4o->value => ModelType::Gpt4o->value,
                        ModelType::Gpt4oMini->value => ModelType::Gpt4oMini->value,
                    ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
