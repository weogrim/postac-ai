<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->enum('kind', ['regular', 'dating'])->default('regular')->after('user_id');
            $table->boolean('is_official')->default(false)->after('kind');
            $table->text('description')->nullable()->after('name');
            $table->text('greeting')->nullable()->after('prompt');
            $table->unsignedInteger('popularity_24h')->default(0)->after('greeting');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn([
                'kind',
                'is_official',
                'description',
                'greeting',
                'popularity_24h',
            ]);
        });
    }
};
