<?php

namespace Rakshitbharat\LaravelStorageWithDatabase;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\FilesystemManager;

class StorageDatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/storage-database.php', 'storage-database');

        $this->app->singleton('storage-database', function ($app) {
            return new StorageDatabaseManager($app);
        });

        $this->app->afterResolving(FilesystemManager::class, function ($manager) {
            $this->extendFilesystemManager($manager);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerMigrations();
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/config/storage-database.php' => config_path('storage-database.php'),
        ], ['storage-database', 'storage-database-config']);

        $this->publishes([
            __DIR__ . '/database/migrations' => database_path('migrations'),
        ], ['storage-database', 'storage-database-migrations']);
    }

    /**
     * Register the package's migrations.
     */
    protected function registerMigrations(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        if ($this->shouldRunMigrations()) {
            $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        }
    }

    /**
     * Extend the Filesystem manager with our custom driver.
     */
    protected function extendFilesystemManager(FilesystemManager $manager): void
    {
        $manager->extend('database', function ($app, $config) {
            $databaseConfig = $this->app['config']['storage-database'];
            return new DatabaseDriver($databaseConfig);
        });
    }

    /**
     * Determine if the migrations should be run.
     */
    protected function shouldRunMigrations(): bool
    {
        $config = $this->app['config']['storage-database'] ?? [];
        return $config['run_migrations'] ?? false;
    }
}
