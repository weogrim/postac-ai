<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Character;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Laravel\Cashier\Subscription;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Użytkownicy', User::query()->count())
                ->description('Wszystkich kont')
                ->color('primary'),

            Stat::make('Postacie', Character::query()->count())
                ->description('Aktywnych')
                ->color('success'),

            Stat::make('Czaty', Chat::query()->count())
                ->description('Wszystkich konwersacji')
                ->color('info'),

            Stat::make('Wiadomości dziś', Message::query()->whereDate('created_at', today())->count())
                ->description('Ostatnie 24h: '.Message::query()->where('created_at', '>=', now()->subDay())->count())
                ->color('warning'),

            Stat::make('Aktywne subskrypcje', Subscription::query()->where('stripe_status', 'active')->count())
                ->description('Premium userzy')
                ->color('success'),
        ];
    }
}
