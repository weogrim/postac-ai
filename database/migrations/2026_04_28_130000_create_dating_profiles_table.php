<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dating_profiles', function (Blueprint $table): void {
            $table->ulid('character_id')->primary();
            $table->smallInteger('age');
            $table->string('city', 64);
            $table->text('bio');
            $table->jsonb('interests')->default(DB::raw("'[]'::jsonb"));
            $table->string('accent_color', 7)->nullable();
            $table->timestamps();

            $table->foreign('character_id')
                ->references('id')->on('characters')
                ->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE dating_profiles ADD CONSTRAINT dating_profiles_age_check CHECK (age >= 18 AND age <= 99)');
    }

    public function down(): void
    {
        Schema::dropIfExists('dating_profiles');
    }
};
