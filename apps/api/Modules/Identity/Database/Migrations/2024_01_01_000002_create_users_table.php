<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 190)->nullable()->unique();
            $table->string('phone', 30)->nullable()->unique();
            $table->string('password', 255);
            $table->string('avatar_path', 255)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->enum('status', ['active', 'suspended', 'banned', 'pending'])->default('pending');
            $table->string('locale', 10)->default('fr');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->string('fcm_token', 255)->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
