<?php

namespace Modules\Categories;

use Illuminate\Support\ServiceProvider;

class CategoriesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        // Pas de loadRoutesFrom() ici — routes/api.php centralise l'inclusion
        // de tous les modules via son glob(). Pas de guessFactoryNamesUsing()
        // non plus — résolution générique déjà centralisée dans AppServiceProvider.
    }
}
