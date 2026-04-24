<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('model_type');
            $table->string('limit_type');
            $table->integer('priority');
            $table->integer('quota');
            $table->integer('used')->default(0);
            $table->timestamp('period_start')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'priority', 'limit_type']);
            $table->index(['limit_type', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_limits');
    }
};
