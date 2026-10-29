<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// «Мой гараж»: автомобили покупателя для быстрого подбора запчастей.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_modification_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable()->comment('Подпись покупателя: «Рабочая Гранта»');
            $table->string('vin', 17)->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'car_modification_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_vehicles');
    }
};
