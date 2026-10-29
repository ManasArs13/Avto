<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Поиск по началу артикула (LIKE '21080350108%') — номера ВАЗ, поиск в админке.
// Обычный btree-индекс PostgreSQL с локалью не-C для LIKE не используется,
// нужен индекс с varchar_pattern_ops.
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX products_article_normalized_pattern_index ON products (article_normalized varchar_pattern_ops)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS products_article_normalized_pattern_index');
        }
    }
};
