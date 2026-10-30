<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Шаблоны документов из resources/legal. Создаются только если их ещё нет —
// отредактированные в админке тексты миграция не трогает.
return new class extends Migration
{
    private const DOCUMENTS = [
        'privacy' => 'Политика обработки персональных данных',
        'personal-data-consent' => 'Согласие на обработку персональных данных',
        'marketing-consent' => 'Согласие на получение рекламных рассылок',
        'offer' => 'Публичная оферта',
        'delivery' => 'Доставка и оплата',
        'returns' => 'Возврат и обмен',
    ];

    public function up(): void
    {
        $sort = 0;
        foreach (self::DOCUMENTS as $slug => $title) {
            $sort += 10;
            if (DB::table('legal_documents')->where('slug', $slug)->exists()) {
                continue;
            }
            DB::table('legal_documents')->insert([
                'slug' => $slug,
                'title' => $title,
                'body' => file_get_contents(resource_path("legal/{$slug}.md")),
                'version' => 1,
                'sort' => $sort,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Название и адрес сайта известны заранее, юридические реквизиты заполняются в админке.
        foreach (['shop_name' => config('app.name'), 'site_url' => config('app.url')] as $key => $value) {
            DB::table('settings')->insertOrIgnore(['key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('legal_documents')->whereIn('slug', array_keys(self::DOCUMENTS))->delete();
    }
};
