<?php

namespace Modules\Suppliers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;

class SuppliersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        Factory::guessFactoryNamesUsing(
            fn (string $modelClass) => 'Modules\\Suppliers\\Database\\Factories\\'
                .class_basename($modelClass).'Factory'
        );
    }
}
