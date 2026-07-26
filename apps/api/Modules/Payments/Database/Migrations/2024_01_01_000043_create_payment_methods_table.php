<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('provider', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('config')->nullable();
            $table->unique(['shop_id', 'code'], 'uq_payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
