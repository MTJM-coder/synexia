<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_inventory_id')->constrained('stock_inventories')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->integer('expected_quantity');
            $table->integer('counted_quantity');
            $table->integer('difference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_inventory_items');
    }
};
