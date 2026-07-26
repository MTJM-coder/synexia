<?php

namespace Modules\Reviews;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;

class ReviewsServiceProvider extends ServiceProvider
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
            fn (string $modelClass) => 'Modules\\Reviews\\Database\\Factories\\'
                .class_basename($modelClass).'Factory'
        );
    }
}
