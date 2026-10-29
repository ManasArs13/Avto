<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Покупатели отдельно от users (сотрудники с доступом в админку).
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable()->index();
            $table->string('password');
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('new')->comment('App\Enums\OrderStatus');
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->string('contact_email')->nullable();
            $table->string('delivery_method')->comment('App\Enums\DeliveryMethod');
            $table->string('delivery_address')->nullable();
            $table->decimal('delivery_cost', 12, 2)->default(0);
            $table->string('payment_method')->comment('App\Enums\PaymentMethod');
            $table->string('payment_status')->default('pending')->comment('App\Enums\PaymentStatus');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            // Снимок на момент заказа: цены и названия могут потом измениться.
            $table->string('brand_name');
            $table->string('article');
            $table->string('name');
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->unsignedSmallInteger('delivery_days')->default(0);
            $table->string('status')->default('new')->comment('App\Enums\OrderItemStatus');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
    }
};
