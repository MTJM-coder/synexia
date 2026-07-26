<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_order_id')->constrained('supplier_orders')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['cash', 'mobile_money', 'bank_transfer', 'check', 'other'])->default('cash');
            $table->timestamp('paid_at')->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('notes', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
