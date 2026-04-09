<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('summary')->nullable();
            $table->string('model_used')->nullable();
            $table->integer('episodes_count')->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('content');
            $table->integer('tokens_used')->nullable();
            $table->string('model_used')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->boolean('consolidated')->default(false);
            $table->timestamps();
        });

        // Добавляем vector колонку (1536 dimensions — совместимо с nomic/deepseek)
        DB::statement('ALTER TABLE episodes ADD COLUMN embedding vector(1536)');

        Schema::create('memories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->float('relevance_score')->default(1.0);
            $table->integer('access_count')->default(0);
            $table->jsonb('source_episodes')->nullable();
            $table->boolean('pinned')->default(false);
            $table->boolean('faded')->default(false);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE memories ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('memories');
        Schema::dropIfExists('episodes');
        Schema::dropIfExists('sessions');
    }
};
