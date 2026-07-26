<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('city', 100)->nullable();
            $table->jsonb('polygon')->nullable();
            $table->decimal('base_fee', 10, 2)->default(0);
            $table->decimal('fee_per_km', 10, 2)->default(0);
            $table->unsignedInteger('estimated_time_minutes')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
