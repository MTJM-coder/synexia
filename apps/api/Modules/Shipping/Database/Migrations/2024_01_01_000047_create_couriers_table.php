<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('vehicle_type', ['moto', 'velo', 'voiture', 'a_pied'])->default('moto');
            $table->string('vehicle_plate', 30)->nullable();
            $table->boolean('is_available')->default(true);
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->unique(['shop_id', 'user_id'], 'uq_courier_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
