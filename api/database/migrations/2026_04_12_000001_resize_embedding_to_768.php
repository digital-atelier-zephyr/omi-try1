<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old columns and recreate with 768 dimensions (nomic-embed-text-v2-moe)
        DB::statement('ALTER TABLE episodes DROP COLUMN IF EXISTS embedding');
        DB::statement('ALTER TABLE episodes ADD COLUMN embedding vector(768)');

        DB::statement('ALTER TABLE memories DROP COLUMN IF EXISTS embedding');
        DB::statement('ALTER TABLE memories ADD COLUMN embedding vector(768)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE episodes DROP COLUMN IF EXISTS embedding');
        DB::statement('ALTER TABLE episodes ADD COLUMN embedding vector(1536)');

        DB::statement('ALTER TABLE memories DROP COLUMN IF EXISTS embedding');
        DB::statement('ALTER TABLE memories ADD COLUMN embedding vector(1536)');
    }
};
