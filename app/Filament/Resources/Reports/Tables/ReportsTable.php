<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reports\Tables;

use App\Reporting\Enums\ReportReason;
use App\Reporting\Enums\ReportStatus;
use App\Reporting\Models\ReportModel;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('reason')
                    ->label('Powód')
                    ->badge()
                    ->sortable(),

                TextColumn::make('reportable_type')
                    ->label('Typ')
                    ->badge(),

                TextColumn::make('reportable_id')
                    ->label('Treść')
                    ->limit(20)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reporter.name')
                    ->label('Zgłaszający')
                    ->placeholder('— guest —')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Opis')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Czeka')
                    ->state(function (ReportModel $record): string {
                        if ($record->status !== ReportStatus::Pending) {
                            return $record->created_at->diffForHumans();
                        }
                        $hours = (int) $record->created_at->diffInHours(now());

                        return $hours < 1 ? '< 1h' : $hours.'h';
                    })
                    ->color(function (ReportModel $record): ?string {
                        if ($record->status !== ReportStatus::Pending) {
                            return null;
                        }

                        return $record->created_at->lt(now()->subHours(24)) ? 'danger' : null;
                    })
                    ->sortable(query: fn (Builder $q, string $dir) => $q->orderBy('created_at', $dir)),

                TextColumn::make('resolved_at')
                    ->label('Rozwiązano')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(ReportStatus::cases())
                        ->mapWithKeys(fn (ReportStatus $s): array => [$s->value => $s->getLabel()])
                        ->all()),

                SelectFilter::make('reason')
                    ->label('Powód')
                    ->options(collect(ReportReason::cases())
                        ->mapWithKeys(fn (ReportReason $r): array => [$r->value => $r->getLabel()])
                        ->all()),

                SelectFilter::make('reportable_type')
                    ->label('Typ treści')
                    ->options([
                        'message' => 'Wiadomość',
                        'character' => 'Postać',
                    ]),

                Filter::make('overdue')
                    ->label('Pilne (>24h)')
                    ->query(fn (Builder $q): Builder => $q->where('status', ReportStatus::Pending->value)
                        ->where('created_at', '<', now()->subHours(24))),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->label('Rozwiąż')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ReportModel $record): bool => $record->status === ReportStatus::Pending)
                    ->requiresConfirmation()
                    ->action(function (ReportModel $record): void {
                        $record->update([
                            'status' => ReportStatus::Resolved,
                            'resolved_by' => auth()->id(),
                            'resolved_at' => now(),
                        ]);
                    }),

                Action::make('dismiss')
                    ->label('Odrzuć')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn (ReportModel $record): bool => $record->status === ReportStatus::Pending)
                    ->requiresConfirmation()
                    ->action(function (ReportModel $record): void {
                        $record->update([
                            'status' => ReportStatus::Dismissed,
                            'resolved_by' => auth()->id(),
                            'resolved_at' => now(),
                        ]);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
