<?php

declare(strict_types=1);

namespace App\Filament\Resources\Consents\Tables;

use App\Legal\Enums\DocumentSlug;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ConsentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')
                    ->label('Użytkownik')
                    ->searchable()
                    ->sortable()
                    ->placeholder('— guest —'),

                TextColumn::make('document.slug')
                    ->label('Dokument')
                    ->badge()
                    ->sortable(),

                TextColumn::make('document.version')
                    ->label('Wersja')
                    ->sortable(),

                TextColumn::make('accepted_at')
                    ->label('Akceptacja')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(40),
            ])
            ->filters([
                SelectFilter::make('legal_document_id')
                    ->label('Dokument')
                    ->relationship('document', 'slug')
                    ->options(fn () => collect(DocumentSlug::cases())
                        ->mapWithKeys(fn (DocumentSlug $s): array => [$s->value => $s->label()])
                        ->all()),
            ])
            ->defaultSort('accepted_at', 'desc');
    }
}
