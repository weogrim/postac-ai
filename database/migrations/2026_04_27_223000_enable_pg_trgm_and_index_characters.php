<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX IF NOT EXISTS characters_name_trgm_idx ON characters USING GIN (name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS characters_description_trgm_idx ON characters USING GIN (description gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS characters_name_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS characters_description_trgm_idx');
    }
};
