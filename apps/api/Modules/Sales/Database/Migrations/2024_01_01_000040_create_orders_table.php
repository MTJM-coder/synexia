<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('order_number', 30);
            $table->foreignId('shop_id')->constrained('shops');
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('delivery_address_id')->nullable()->constrained('customer_addresses');
            $table->enum('fulfillment_type', ['delivery', 'pickup'])->default('delivery');
            $table->enum('status', ['cart', 'pending_payment', 'confirmed', 'preparing', 'shipped', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'refunded', 'failed'])->default('pending_payment');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('XAF');
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->string('customer_note', 500)->nullable();
            $table->string('internal_note', 500)->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->unique(['shop_id', 'order_number'], 'uq_order_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
