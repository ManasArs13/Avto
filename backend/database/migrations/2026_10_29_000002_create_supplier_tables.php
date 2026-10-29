<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->comment('App\Enums\SupplierType');
            $table->unsignedSmallInteger('delivery_days')->default(0)->comment('Добавляется к сроку из прайса');
            $table->jsonb('settings')->nullable()->comment('Маппинг колонок прайса, доступы к API');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Предложение: конкретный товар у конкретного поставщика.
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('purchase_price', 12, 2);
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedSmallInteger('delivery_days')->default(0);
            $table->unsignedSmallInteger('multiplicity')->default(1)->comment('Кратность продажи');
            $table->timestamps();

            $table->unique(['supplier_id', 'product_id']);
            $table->index('product_id');
        });

        Schema::create('markup_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('supplier_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('price_from', 12, 2)->default(0);
            $table->decimal('price_to', 12, 2)->nullable();
            $table->decimal('percent', 6, 2);
            $table->unsignedInteger('priority')->default(0)->comment('Больше — проверяется раньше');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('price_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('status')->default('pending')->comment('App\Enums\ImportStatus');
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_imports');
        Schema::dropIfExists('markup_rules');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('suppliers');
    }
};
