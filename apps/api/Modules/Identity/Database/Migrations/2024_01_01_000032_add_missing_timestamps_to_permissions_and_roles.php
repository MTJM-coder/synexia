<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige une migration antérieure de `permissions` (et `roles`, par
 * précaution — même symptôme probable) qui n'avait pas de `timestamps()`
 * complet. Idempotent : ne touche que les colonnes manquantes.
 *
 * NOTE : renommez le préfixe de timestamp pour qu'il vienne APRÈS la
 * migration originale de création de ces deux tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['permissions', 'roles'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'created_at')) {
                    $blueprint->timestamp('created_at')->nullable();
                }
                if (! Schema::hasColumn($table, 'updated_at')) {
                    $blueprint->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Volontairement pas de rollback destructeur des timestamps.
    }
};
