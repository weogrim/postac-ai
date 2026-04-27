<?php

declare(strict_types=1);

namespace App\User\Commands;

use App\User\Models\UserModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GcGuestUsersCommand extends Command
{
    protected $signature = 'users:gc-guests {--inactive-days=7}';

    protected $description = 'Usuwa konta gości (email NULL) bez wiadomości od --inactive-days dni.';

    public function handle(): int
    {
        $days = (int) $this->option('inactive-days');
        $threshold = now()->subDays($days);

        $activeUserIds = DB::table('messages')
            ->where('created_at', '>', $threshold)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $deleted = 0;

        UserModel::query()
            ->guests()
            ->where('created_at', '<', $threshold)
            ->whereNotIn('id', $activeUserIds)
            ->chunkById(100, function ($users) use (&$deleted): void {
                foreach ($users as $user) {
                    /** @var UserModel $user */
                    $user->delete();
                    $deleted++;
                }
            });

        $this->info("Usunięto {$deleted} kont gości nieaktywnych przez >{$days} dni.");

        return self::SUCCESS;
    }
}
