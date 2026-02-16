<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

final class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $modulesPath = app_path('Modules');

        if (! is_dir($modulesPath)) {
            return;
        }

        /** @var array<int, string> $directories */
        $directories = glob($modulesPath.'/*', GLOB_ONLYDIR) ?: [];

        foreach ($directories as $modulePath) {
            $moduleName = basename($modulePath);

            if (Str::startsWith($moduleName, '_')) {
                continue;
            }

            $this->registerModuleProvider($moduleName);
        }
    }

    public function boot(): void
    {
        $modulesPath = app_path('Modules');

        if (! is_dir($modulesPath)) {
            return;
        }

        /** @var array<int, string> $directories */
        $directories = glob($modulesPath.'/*', GLOB_ONLYDIR) ?: [];

        foreach ($directories as $modulePath) {
            $moduleName = basename($modulePath);

            if (Str::startsWith($moduleName, '_')) {
                continue;
            }

            $this->loadModuleRoutes($modulePath);
            $this->loadModuleMigrations($modulePath);
        }
    }

    private function registerModuleProvider(string $moduleName): void
    {
        $providerClass = "App\\Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";

        if (class_exists($providerClass)) {
            $this->app->register($providerClass);
        }
    }

    private function loadModuleRoutes(string $modulePath): void
    {
        $routesFile = $modulePath.'/routes.php';

        if (! file_exists($routesFile)) {
            return;
        }

        Route::middleware('api')
            ->prefix('api')
            ->group($routesFile);
    }

    private function loadModuleMigrations(string $modulePath): void
    {
        $migrationsPath = $modulePath.'/Database/Migrations';

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }
}
