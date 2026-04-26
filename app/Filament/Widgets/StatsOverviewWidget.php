<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Character\Models\CharacterModel;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageModel;
use App\User\Models\UserModel;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Laravel\Cashier\Subscription;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Użytkownicy', UserModel::query()->count())
                ->description('Wszystkich kont')
                ->color('primary'),

            Stat::make('Postacie', CharacterModel::query()->count())
                ->description('Aktywnych')
                ->color('success'),

            Stat::make('Czaty', ChatModel::query()->count())
                ->description('Wszystkich konwersacji')
                ->color('info'),

            Stat::make('Wiadomości dziś', MessageModel::query()->whereDate('created_at', today())->count())
                ->description('Ostatnie 24h: '.MessageModel::query()->where('created_at', '>=', now()->subDay())->count())
                ->color('warning'),

            Stat::make('Aktywne subskrypcje', Subscription::query()->where('stripe_status', 'active')->count())
                ->description('Premium userzy')
                ->color('success'),
        ];
    }
}
