<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Упрощённая применяемость. В проде данные обычно приходят из TecDoc / Laximo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_makes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_make_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->timestamps();

            $table->unique(['car_make_id', 'slug']);
        });

        Schema::create('car_modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('Напр. 2.5 Hybrid (218 л.с.)');
            $table->string('engine_code')->nullable();
            $table->decimal('engine_volume', 3, 1)->nullable();
            $table->unsignedSmallInteger('power_hp')->nullable();
            $table->string('fuel')->nullable();
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->timestamps();
        });

        Schema::create('car_modification_product', function (Blueprint $table) {
            $table->foreignId('car_modification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->primary(['car_modification_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_modification_product');
        Schema::dropIfExists('car_modifications');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('car_makes');
    }
};
