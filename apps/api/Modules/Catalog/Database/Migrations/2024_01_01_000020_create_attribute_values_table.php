<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_type_id')->constrained('attribute_types')->cascadeOnDelete();
            $table->string('value', 100);
            $table->string('hex_color', 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
