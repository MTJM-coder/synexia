<?php

namespace App\Providers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
         Factory::guessFactoryNamesUsing(function (string $modelClass) {
            // Modules\{Module}\Models\{Model} -> Modules\{Module}\Database\Factories\{Model}Factory
            if (preg_match('/^Modules\\\\([^\\\\]+)\\\\Models\\\\(.+)$/', $modelClass, $matches)) {
                [, $module, $modelBasename] = $matches;
 
                return "Modules\\{$module}\\Database\\Factories\\{$modelBasename}Factory";
            }
 
            // Repli sur la convention Laravel standard (app/Models/...).
            return 'Database\\Factories\\'.class_basename($modelClass).'Factory';
        });
    
    }
}
