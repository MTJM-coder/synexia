<?php

namespace Modules\Marketplace;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Marketplace\Contracts\CommissionCalculatorContract;
use Modules\Marketplace\Contracts\PlanLimitCheckerContract;
use Modules\Marketplace\Events\ShopStatusChanged;
use Modules\Marketplace\Listeners\LogShopStatusChange;
use Modules\Marketplace\Services\CommissionCalculator;
use Modules\Marketplace\Services\PlanLimitChecker;

class MarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PlanLimitCheckerContract::class, PlanLimitChecker::class);
        $this->app->singleton(CommissionCalculatorContract::class, CommissionCalculator::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        Event::listen(ShopStatusChanged::class, [LogShopStatusChange::class, 'handle']);
    }
}
