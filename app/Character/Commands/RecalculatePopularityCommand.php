<?php

declare(strict_types=1);

namespace App\Character\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculatePopularityCommand extends Command
{
    protected $signature = 'characters:recalc-popularity';

    protected $description = 'Aktualizuje popularity_24h dla każdej postaci na podstawie liczby unikalnych chatów z wiadomościami w ostatnich 24h.';

    public function handle(): int
    {
        $sql = <<<'SQL'
            UPDATE characters c SET popularity_24h = COALESCE(sub.cnt, 0)
            FROM (
                SELECT ch.character_id, COUNT(DISTINCT ch.id) AS cnt
                FROM chats ch
                INNER JOIN messages m ON m.chat_id = ch.id
                WHERE m.created_at > NOW() - INTERVAL '24 hours'
                GROUP BY ch.character_id
            ) sub
            WHERE c.id = sub.character_id

            SQL;

        $updated = DB::affectingStatement($sql);

        DB::statement('UPDATE characters SET popularity_24h = 0 WHERE NOT EXISTS (SELECT 1 FROM chats ch INNER JOIN messages m ON m.chat_id = ch.id WHERE ch.character_id = characters.id AND m.created_at > NOW() - INTERVAL \'24 hours\') AND popularity_24h > 0');

        $this->info("Zaktualizowano popularity_24h dla {$updated} postaci.");

        return self::SUCCESS;
    }
}
