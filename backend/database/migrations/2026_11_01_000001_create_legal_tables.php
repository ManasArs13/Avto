<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Требования законодательства РФ: реквизиты продавца (ЗоЗПП, правила дистанционной продажи),
// публикуемые документы и журнал согласий на обработку персональных данных (152-ФЗ).
return new class extends Migration
{
    public function up(): void
    {
        // Реквизиты и контакты магазина: ключ → значение, редактируются в админке.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('body')->comment('Markdown; {{переменные}} подставляются из реквизитов');
            $table->unsignedInteger('version')->default(1)->comment('Растёт при каждом изменении текста');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        // Доказательство согласия: кто, когда, с какого адреса и на какую редакцию документа.
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32);
            $table->boolean('granted')->comment('false — отзыв согласия');
            $table->string('document_slug')->nullable();
            $table->unsignedInteger('document_version')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['customer_id', 'type']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('marketing_consent')->default(false)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('marketing_consent');
        });
        Schema::dropIfExists('consents');
        Schema::dropIfExists('legal_documents');
        Schema::dropIfExists('settings');
    }
};
