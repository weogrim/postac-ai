<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table): void {
            $table->string('slug', 160)->nullable()->after('name');
            $table->unique('slug');
        });

        $rows = DB::table('characters')
            ->where('kind', 'regular')
            ->whereNull('slug')
            ->select(['id', 'name'])
            ->get();

        $taken = DB::table('characters')
            ->whereNotNull('slug')
            ->pluck('slug')
            ->all();

        foreach ($rows as $row) {
            $base = Str::slug((string) $row->name) ?: 'postac';
            $candidate = $base;
            $counter = 2;
            while (in_array($candidate, $taken, true)) {
                $candidate = $base.'-'.$counter;
                $counter++;
            }
            $taken[] = $candidate;

            DB::table('characters')->where('id', $row->id)->update(['slug' => $candidate]);
        }
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
