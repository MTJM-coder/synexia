<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->enum('type', ['low_stock', 'critical_stock', 'out_of_stock', 'expiring_soon', 'expired']);
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('triggered_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
