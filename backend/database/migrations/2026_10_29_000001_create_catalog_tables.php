<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('country')->nullable();
            $table->boolean('is_oem')->default(false)->comment('Производитель автомобилей (оригинал)');
            $table->string('logo')->nullable();
            $table->timestamps();
        });

        // Поставщики пишут бренды по-разному: "TOYOTA/LEXUS", "MANN FILTER" и т.п.
        Schema::create('brand_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('alias')->unique()->comment('Нормализованное написание');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('article')->comment('Артикул как у производителя');
            $table->string('article_normalized')->index()->comment('Без пробелов, дефисов, в верхнем регистре');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->jsonb('specs')->comment('Характеристики: размеры, резьба и т.п.')->nullable();
            $table->jsonb('images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['brand_id', 'article_normalized']);
        });

        // Аналоги. Храним обе стороны связи, чтобы искать одним запросом.
        Schema::create('crosses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('analog_id')->constrained('products')->cascadeOnDelete();
            $table->string('source')->default('manual');
            $table->timestamps();

            $table->unique(['product_id', 'analog_id']);
            $table->index('analog_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crosses');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brand_aliases');
        Schema::dropIfExists('brands');
    }
};
