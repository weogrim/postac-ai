<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TokenUsageChart extends ChartWidget
{
    protected ?string $heading = 'Zużycie tokenów (ostatnie 30 dni)';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $from = now()->subDays(29)->startOfDay();

        $rows = DB::table('messages')
            ->select(
                DB::raw("to_char(date_trunc('day', created_at), 'YYYY-MM-DD') as day"),
                DB::raw('SUM(tokens_usage) as total'),
            )
            ->where('created_at', '>=', $from)
            ->whereNotNull('tokens_usage')
            ->groupBy('day')
            ->get();

        $days = collect(range(0, 29))
            ->map(fn (int $offset): string => $from->copy()->addDays($offset)->toDateString())
            ->values();

        $series = $days->map(fn (string $day): int => (int) $rows->firstWhere('day', $day)?->total);

        return [
            'datasets' => [
                [
                    'label' => 'Tokeny',
                    'data' => $series->values()->all(),
                    'borderColor' => 'rgba(14, 165, 233, 1)',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.2)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $days->map(fn (string $day): string => Carbon::parse($day)->format('d.m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
