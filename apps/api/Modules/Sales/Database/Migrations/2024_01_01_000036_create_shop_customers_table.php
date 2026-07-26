<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_spent', 14, 2)->default(0);
            $table->text('internal_notes')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('first_order_at')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->unique(['shop_id', 'user_id'], 'uq_shop_customer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_customers');
    }
};
