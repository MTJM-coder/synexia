<?php

namespace Modules\Payments;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;

class PaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings de Contracts -> implémentations concrètes.
        // Ex: $this->app->singleton(SomeContract::class, SomeService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        // Enregistrement des Events/Listeners du module : voir Modules\Identity
        // ou Modules\Inventory pour un exemple concret (Event::listen(...)).
    }
}
