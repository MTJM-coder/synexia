<?php

namespace Modules\Identity;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Identity\Contracts\PermissionResolverContract;
use Modules\Identity\Contracts\ShopMembershipContract;
use Modules\Identity\Events\EmployeePermissionOverridden;
use Modules\Identity\Events\EmployeeReactivated;
use Modules\Identity\Events\EmployeeRemoved;
use Modules\Identity\Events\EmployeeRoleAssigned;
use Modules\Identity\Events\EmployeeSuspended;
use Modules\Identity\Listeners\InvalidatePermissionCache;
use Modules\Identity\Listeners\LogPermissionChange;
use Modules\Identity\Services\PermissionResolver;
use Modules\Identity\Services\ShopMembershipService;

/**
 * IMPORTANT : ce ServiceProvider ne doit JAMAIS importer ni écouter quoi
 * que ce soit venant de Modules\Marketplace. Identity est une fondation ;
 * c'est Marketplace qui dépend d'Identity (via ShopMembershipContract,
 * appelé directement par Marketplace\Actions\CreateShopAction), jamais
 * l'inverse.
 */
class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionResolverContract::class, PermissionResolver::class);
        $this->app->bind(ShopMembershipContract::class, ShopMembershipService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        // loadRoutesFrom() volontairement absent — routes/api.php centralise
        // l'inclusion de tous les modules via son glob().

        $this->registerEventListeners();
    }

    private function registerEventListeners(): void
    {
        Event::listen(EmployeeRoleAssigned::class, [InvalidatePermissionCache::class, 'handleRoleAssigned']);
        Event::listen(EmployeeRoleAssigned::class, [LogPermissionChange::class, 'handleRoleAssigned']);

        Event::listen(EmployeePermissionOverridden::class, [InvalidatePermissionCache::class, 'handlePermissionOverridden']);
        Event::listen(EmployeePermissionOverridden::class, [LogPermissionChange::class, 'handlePermissionOverridden']);

        Event::listen(EmployeeSuspended::class, [InvalidatePermissionCache::class, 'handleSuspended']);
        Event::listen(EmployeeSuspended::class, [LogPermissionChange::class, 'handleSuspended']);

        Event::listen(EmployeeReactivated::class, [InvalidatePermissionCache::class, 'handleReactivated']);
        Event::listen(EmployeeReactivated::class, [LogPermissionChange::class, 'handleReactivated']);

        Event::listen(EmployeeRemoved::class, [InvalidatePermissionCache::class, 'handleRemoved']);
        Event::listen(EmployeeRemoved::class, [LogPermissionChange::class, 'handleRemoved']);
    }
}
