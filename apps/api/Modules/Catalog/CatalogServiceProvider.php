<?php

namespace Modules\Catalog;

use Illuminate\Support\ServiceProvider;

class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ProductPolicy dépend de Modules\Identity\Contracts\PermissionResolverContract,
        // déjà lié en singleton dans Modules\Identity\IdentityServiceProvider —
        // rien à lier ici, l'injection automatique du conteneur suffit.
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        // Pas de loadRoutesFrom() — routes/api.php centralise l'inclusion.
        // Pas de guessFactoryNamesUsing() — résolution générique dans AppServiceProvider.
    }
}
