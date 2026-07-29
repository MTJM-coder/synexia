<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_employee_permissions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('shop_employee_id')->constrained('shop_employees')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->boolean('is_granted')->default(true);
            $table->unique(['shop_employee_id', 'permission_id'], 'uq_sep');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_employee_permissions');
    }
};
