<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->enum('guard_scope', ['platform', 'shop']);
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->boolean('is_system')->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->unique(['slug', 'shop_id'], 'uq_role_slug_shop');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
