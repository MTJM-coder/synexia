<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('XAF');
            $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'refunded', 'partially_refunded'])->default('pending');
            $table->string('provider_reference', 150)->nullable();
            $table->string('payer_phone', 30)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('failed_reason', 255)->nullable();
            $table->jsonb('raw_response')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
