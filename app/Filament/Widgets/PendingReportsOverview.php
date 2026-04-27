<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Reporting\Models\ReportModel;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingReportsOverview extends BaseWidget
{
    protected ?string $heading = 'Moderacja';

    protected function getStats(): array
    {
        $pending = ReportModel::query()->pending()->count();
        $overdue = ReportModel::query()->overdue()->count();
        $resolvedToday = ReportModel::query()
            ->whereDate('resolved_at', today())
            ->count();

        return [
            Stat::make('Oczekujące zgłoszenia', $pending)
                ->description($pending > 0 ? 'Wymaga moderacji' : 'Brak nowych')
                ->color($pending > 0 ? 'warning' : 'success'),

            Stat::make('Pilne (>24h)', $overdue)
                ->description($overdue > 0 ? 'Naruszenie SLA' : 'SLA OK')
                ->color($overdue > 0 ? 'danger' : 'success'),

            Stat::make('Rozwiązane dziś', $resolvedToday)
                ->description('Mod team progress')
                ->color('info'),
        ];
    }
}
