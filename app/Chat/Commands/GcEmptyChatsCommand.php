<?php

declare(strict_types=1);

namespace App\Chat\Commands;

use App\Chat\Enums\SenderRole;
use App\Chat\Models\ChatModel;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class GcEmptyChatsCommand extends Command
{
    protected $signature = 'chats:gc-empty {--days=7}';

    protected $description = 'Hard delete chatów starszych niż --days dni bez żadnej wiadomości od użytkownika.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->subDays($days);
        $deleted = 0;

        ChatModel::query()
            ->where('created_at', '<', $threshold)
            ->whereDoesntHave(
                'messages',
                fn (Builder $q) => $q->where('sender_role', SenderRole::User->value)
            )
            ->chunkById(100, function ($chats) use (&$deleted): void {
                foreach ($chats as $chat) {
                    /** @var ChatModel $chat */
                    $chat->forceDelete();
                    $deleted++;
                }
            });

        $this->info("Usunięto {$deleted} pustych chatów starszych niż {$days} dni.");

        return self::SUCCESS;
    }
}
