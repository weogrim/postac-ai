<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Chat\Enums\SenderRole;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MessagesPerDayChart extends ChartWidget
{
    protected ?string $heading = 'Wiadomości (ostatnie 30 dni)';

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
                'sender_role',
                DB::raw('COUNT(*) as total'),
            )
            ->where('created_at', '>=', $from)
            ->groupBy('day', 'sender_role')
            ->get();

        $days = collect(range(0, 29))
            ->map(fn (int $offset): string => $from->copy()->addDays($offset)->toDateString())
            ->values();

        $userSeries = $days->map(fn (string $day): int => (int) $rows->firstWhere(
            fn (object $row): bool => $row->day === $day && $row->sender_role === SenderRole::User->value
        )?->total);

        $characterSeries = $days->map(fn (string $day): int => (int) $rows->firstWhere(
            fn (object $row): bool => $row->day === $day && $row->sender_role === SenderRole::Character->value
        )?->total);

        return [
            'datasets' => [
                [
                    'label' => 'Użytkownicy',
                    'data' => $userSeries->values()->all(),
                    'backgroundColor' => 'rgba(99, 102, 241, 0.6)',
                ],
                [
                    'label' => 'Postacie',
                    'data' => $characterSeries->values()->all(),
                    'backgroundColor' => 'rgba(244, 114, 182, 0.6)',
                ],
            ],
            'labels' => $days->map(fn (string $day): string => Carbon::parse($day)->format('d.m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
