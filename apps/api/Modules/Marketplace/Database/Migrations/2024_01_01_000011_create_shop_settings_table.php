<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->string('currency', 3)->default('XAF');
            $table->string('language', 10)->default('fr');
            $table->string('timezone', 60)->default('Africa/Douala');
            $table->decimal('tax_rate', 5, 2)->default(19.25);
            $table->boolean('tax_inclusive')->default(true);
            $table->jsonb('opening_hours')->nullable();
            $table->boolean('allow_pickup')->default(true);
            $table->boolean('allow_delivery')->default(true);
            $table->decimal('delivery_radius_km', 6, 2)->nullable();
            $table->jsonb('email_config')->nullable();
            $table->jsonb('sms_config')->nullable();
            $table->jsonb('whatsapp_config')->nullable();
            $table->jsonb('notification_preferences')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->unique(['shop_id'], 'uq_shop_settings');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
    }
};
