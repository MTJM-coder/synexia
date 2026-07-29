<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Génère le squelette d'un module Synexia, conforme à docs/ARCHITECTURE.md.
 *
 * Usage :
 *   php artisan make:module Sales
 *   php artisan make:module Categories --simple
 *
 * Ne modifie JAMAIS bootstrap/providers.php automatiquement — l'ajout du
 * ServiceProvider s'y fait à la main, volontairement (décision d'équipe).
 */
class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name : Nom du module en PascalCase, ex: Sales}
                            {--simple : Structure CRUD minimale au lieu de la structure DDD complète}';

    protected $description = "Scaffold un nouveau module Synexia (Modules/{Nom}) conforme à docs/ARCHITECTURE.md";

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $kebab = Str::kebab(Str::pluralStudly($name));
        $isSimple = (bool) $this->option('simple');

        $modulePath = base_path("Modules/{$name}");

        if (File::isDirectory($modulePath)) {
            $this->components->error("Le module « {$name} » existe déjà dans Modules/{$name}.");

            return self::FAILURE;
        }

        $this->components->info(
            $isSimple
                ? "Génération du module simple « {$name} »..."
                : "Génération du module riche (DDD) « {$name} »..."
        );

        $this->createDirectoryStructure($modulePath, $isSimple);
        $this->createServiceProvider($modulePath, $name, $isSimple);
        $this->createRoutesFile($modulePath, $kebab);

        $this->components->info("Module « {$name} » créé dans Modules/{$name}.");
        $this->newLine();
        $this->components->warn('Étape manuelle requise — ajoute cette ligne dans bootstrap/providers.php :');
        $this->line("    Modules\\{$name}\\{$name}ServiceProvider::class,");
        $this->newLine();

        if (! $isSimple) {
            $this->components->info(
                "Rappel (voir docs/ARCHITECTURE.md) : ne crée Domain/, Contracts/, Policies/, ".
                "Exceptions/ et Observers/ que le jour où un besoin concret apparaît — pas avant."
            );
        }

        return self::SUCCESS;
    }

    private function createDirectoryStructure(string $modulePath, bool $isSimple): void
    {
        $always = [
            'Models',
            'Http/Controllers',
            'Http/Requests',
            'Http/Resources',
            'Database/Migrations',
            'Database/Factories',
            'Database/Seeders',
        ];

        $richOnly = [
            'Actions',
            'Services',
            'Events',
            'Listeners',
            'Tests/Unit',
            'Tests/Feature',
        ];

        $simpleOnly = [
            'Tests',
        ];

        $directories = $isSimple
            ? array_merge($always, $simpleOnly)
            : array_merge($always, $richOnly);

        foreach ($directories as $relative) {
            $fullPath = "{$modulePath}/{$relative}";
            File::makeDirectory($fullPath, 0755, recursive: true);

            // .gitkeep pour que git conserve les dossiers vides tant qu'aucune
            // classe concrète n'y a encore été ajoutée.
            File::put("{$fullPath}/.gitkeep", '');
        }
    }

    private function createServiceProvider(string $modulePath, string $name, bool $isSimple): void
    {
        $stub = $isSimple
            ? $this->simpleServiceProviderStub($name)
            : $this->richServiceProviderStub($name);

        File::put("{$modulePath}/{$name}ServiceProvider.php", $stub);
    }

    private function createRoutesFile(string $modulePath, string $kebab): void
    {
        $stub = <<<PHP
        <?php

        use Illuminate\Support\Facades\Route;

        Route::prefix('v1/{$kebab}')->name('{$kebab}.')->group(function () {
            // Route::apiResource('/', Controller::class);
        });

        PHP;

        File::put("{$modulePath}/routes.php", $stub);
    }

    private function richServiceProviderStub(string $name): string
    {
        return <<<PHP
        <?php

        namespace Modules\\{$name};

        use Illuminate\Database\Eloquent\Factories\Factory;
        use Illuminate\Support\ServiceProvider;

        class {$name}ServiceProvider extends ServiceProvider
        {
            public function register(): void
            {
                // Bindings de Contracts -> implémentations concrètes.
                // Ex: \$this->app->singleton(SomeContract::class, SomeService::class);
            }

            public function boot(): void
            {
                \$this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
                \$this->loadRoutesFrom(__DIR__.'/routes.php');

                // Permet à \$model->factory() de trouver les factories du module
                // (elles ne vivent pas dans database/factories comme Laravel l'attend par défaut).
                Factory::guessFactoryNamesUsing(
                    fn (string \$modelClass) => 'Modules\\\\{$name}\\\\Database\\\\Factories\\\\'
                        .class_basename(\$modelClass).'Factory'
                );

                // Enregistrement des Events/Listeners du module : voir Modules\\Identity
                // ou Modules\\Inventory pour un exemple concret (Event::listen(...)).
            }
        }

        PHP;
    }

    private function simpleServiceProviderStub(string $name): string
    {
        return <<<PHP
        <?php

        namespace Modules\\{$name};

        use Illuminate\Database\Eloquent\Factories\Factory;
        use Illuminate\Support\ServiceProvider;

        class {$name}ServiceProvider extends ServiceProvider
        {
            public function register(): void
            {
                //
            }

            public function boot(): void
            {
                \$this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

                // Factory::guessFactoryNamesUsing(
                //     fn (string \$modelClass) => 'Modules\\\\{$name}\\\\Database\\\\Factories\\\\'
                //         .class_basename(\$modelClass).'Factory'
                // );
            }
        }

        PHP;
    }
}
