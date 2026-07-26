<?php

namespace Modules\Accounting;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;

class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings de Contracts -> implémentations concrètes.
        // Ex: $this->app->singleton(SomeContract::class, SomeService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Permet à $model->factory() de trouver les factories du module
        // (elles ne vivent pas dans database/factories comme Laravel l'attend par défaut).
        Factory::guessFactoryNamesUsing(
            fn (string $modelClass) => 'Modules\\Accounting\\Database\\Factories\\'
                .class_basename($modelClass).'Factory'
        );

        // Enregistrement des Events/Listeners du module : voir Modules\Identity
        // ou Modules\Inventory pour un exemple concret (Event::listen(...)).
    }
}
