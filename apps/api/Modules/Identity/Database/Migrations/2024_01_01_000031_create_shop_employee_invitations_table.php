<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NOTE : renommez le préfixe de timestamp de ce fichier pour qu'il vienne
 * chronologiquement APRÈS les migrations existantes de Identity (roles,
 * permissions, shop_employees, shop_employee_permissions, login_histories)
 * chez vous — le nom exact ici est indicatif, pas figé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_employee_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->string('email', 190);
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();

            // Hash SHA-256 du token — jamais le token en clair (voir InviteEmployeeAction).
            $table->string('token', 64)->unique();

            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->string('status', 20)->default('pending'); // pending|accepted|expired|cancelled

            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_employee_invitations');
    }
};
