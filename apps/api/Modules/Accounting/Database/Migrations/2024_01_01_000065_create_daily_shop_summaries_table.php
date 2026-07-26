<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_shop_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->date('summary_date');
            $table->unsignedInteger('orders_count')->default(0);
            $table->decimal('gross_revenue', 14, 2)->default(0);
            $table->decimal('discounts_total', 14, 2)->default(0);
            $table->decimal('commission_total', 14, 2)->default(0);
            $table->decimal('refunds_total', 14, 2)->default(0);
            $table->decimal('net_revenue', 14, 2)->default(0);
            $table->decimal('cost_of_goods', 14, 2)->default(0);
            $table->decimal('estimated_profit', 14, 2)->default(0);
            $table->unsignedInteger('new_customers_count')->default(0);
            $table->unique(['shop_id', 'summary_date'], 'uq_daily_summary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_shop_summaries');
    }
};
