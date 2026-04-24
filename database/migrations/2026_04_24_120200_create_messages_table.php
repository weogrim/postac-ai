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
        DB::statement("CREATE TYPE sender_role AS ENUM ('user', 'character')");

        Schema::create('messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUlid('character_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->string('model')->nullable();
            $table->integer('tokens_usage')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['chat_id', 'created_at']);
        });

        DB::statement('ALTER TABLE messages ADD COLUMN sender_role sender_role NOT NULL');
        DB::statement(<<<'SQL'
            ALTER TABLE messages ADD CONSTRAINT messages_sender_check CHECK (
                (sender_role = 'user' AND user_id IS NOT NULL AND character_id IS NULL)
                OR (sender_role = 'character' AND character_id IS NOT NULL AND user_id IS NULL)
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        DB::statement('DROP TYPE IF EXISTS sender_role');
    }
};
