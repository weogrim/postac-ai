<?php

declare(strict_types=1);

namespace App\Filament\Resources\LegalDocuments\Tables;

use App\Legal\Enums\DocumentSlug;
use App\Legal\Models\LegalDocumentModel;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LegalDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->label('Typ')
                    ->badge()
                    ->sortable(),

                TextColumn::make('version')
                    ->label('Wersja')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable()
                    ->limit(60),

                IconColumn::make('published_at')
                    ->label('Opublikowany')
                    ->boolean()
                    ->getStateUsing(fn (LegalDocumentModel $record): bool => $record->isPublished()),

                TextColumn::make('published_at')
                    ->label('Data publikacji')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('consents_count')
                    ->label('Zgód')
                    ->counts('consents')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('slug')
                    ->label('Typ')
                    ->options(DocumentSlug::class),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('duplicate')
                    ->label('Nowa wersja')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (LegalDocumentModel $record): void {
                        $maxVersion = LegalDocumentModel::query()
                            ->where('slug', $record->slug)
                            ->max('version');

                        LegalDocumentModel::create([
                            'slug' => $record->slug,
                            'version' => ((int) $maxVersion) + 1,
                            'title' => $record->title,
                            'content' => $record->content,
                            'published_at' => null,
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('slug')
            ->defaultGroup('slug');
    }
}
