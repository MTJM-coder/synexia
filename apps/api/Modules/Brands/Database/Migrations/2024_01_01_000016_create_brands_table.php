<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug', 180);
            $table->string('logo_path', 255)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->unique(['slug', 'shop_id'], 'uq_brand_slug_shop');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
