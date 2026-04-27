<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64);
            $table->unsignedInteger('version');
            $table->string('title');
            $table->text('content');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['slug', 'version']);
            $table->index(['slug', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
    }
};
