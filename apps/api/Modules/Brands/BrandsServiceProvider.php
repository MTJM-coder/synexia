<?php

namespace Modules\Brands;

use Illuminate\Support\ServiceProvider;

class BrandsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
