<?php

namespace Modules\Inventory;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Contracts\WarehouseProvisioningContract;
use Modules\Inventory\Services\WarehouseProvisioningService;

class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WarehouseProvisioningContract::class, WarehouseProvisioningService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        // NE PAS appeler loadRoutesFrom() ici — routes/api.php centralise
        // l'inclusion de tous les modules via son glob().

        // NE PAS appeler Factory::guessFactoryNamesUsing() ici — résolution
        // générique centralisée une seule fois dans App\Providers\AppServiceProvider.
    }
}
