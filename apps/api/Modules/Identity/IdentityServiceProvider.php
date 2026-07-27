<?php

namespace Modules\Identity;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Identity\Contracts\PermissionResolverContract;
use Modules\Identity\Events\EmployeePermissionOverridden;
use Modules\Identity\Events\EmployeeRoleAssigned;
use Modules\Identity\Listeners\InvalidatePermissionCache;
use Modules\Identity\Listeners\LogPermissionChange;
use Modules\Identity\Policies\ShopEmployeePolicy;
use Modules\Identity\Services\PermissionResolver;

class IdentityServiceProvider extends ServiceProvider
{
   
    public function register(): void
    {
        $this->app->singleton(PermissionResolverContract::class, PermissionResolver::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        // Permet à Model::factory() de trouver les factories du module,
        // qui ne vivent pas dans database/factories comme Laravel l'attend par défaut.
        Factory::guessFactoryNamesUsing(
            fn (string $modelClass) => 'Modules\\Identity\\Database\\Factories\\'
                .class_basename($modelClass).'Factory'
        );

        $this->registerPolicies();
        $this->registerEventListeners();
    }

    private function registerPolicies(): void
    {
        // ShopEmployee n'a pas de Policy Laravel "standard" au sens strict
        // (les vérifications se font sur shop_id + permission plutôt que sur
        // le modèle seul), donc on l'enregistre manuellement plutôt que via
        // Gate::policy() automatique.
        $this->app->bind(ShopEmployeePolicy::class, function ($app) {
            return new ShopEmployeePolicy($app->make(PermissionResolverContract::class));
        });
    }

    private function registerEventListeners(): void
    {
        Event::listen(
            EmployeeRoleAssigned::class,
            [InvalidatePermissionCache::class, 'handleRoleAssigned'],
        );
        Event::listen(
            EmployeeRoleAssigned::class,
            [LogPermissionChange::class, 'handleRoleAssigned'],
        );

        Event::listen(
            EmployeePermissionOverridden::class,
            [InvalidatePermissionCache::class, 'handlePermissionOverridden'],
        );
        Event::listen(
            EmployeePermissionOverridden::class,
            [LogPermissionChange::class, 'handlePermissionOverridden'],
        );
    }
}
