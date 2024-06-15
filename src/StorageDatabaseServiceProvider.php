<?php

namespace Rakshitbharat\LaravelStorageWithDatabase;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\FilesystemManager;

class StorageDatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/storage-database.php', 'storage-database');

        $this->app->singleton('storage-database', function ($app) {
            return new StorageDatabaseManager($app);
        });

        $this->app->afterResolving(FilesystemManager::class, function ($manager, $app) {
            $manager->extend('database', function ($config, $app) {
                return new DatabaseDriver(config('storage-database'));
            });

            return $manager;
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/storage-database.php' => config_path('storage-database.php'),
        ], 'config');

        if ($this->isPublishingMigrations()) {
            $migrationPath = database_path('migrations/' . date('Y_m_d_His', time()) . '_create_storage_table.php');
            $this->publishes([
                $this->publishMigration($migrationPath) => $migrationPath,
            ], 'migrations');
        }
    }

    protected function isPublishingMigrations()
    {
        $publishingMigrations = false;
        $args = $_SERVER['argv'];

        foreach ($args as $arg) {
            if (strpos($arg, '--tag=migrations') !== false) {
                $publishingMigrations = true;
                break;
            }
        }

        return $publishingMigrations;
    }

    protected function publishMigration($migrationPath)
    {
        $migrationContent = $this->getMigrationOutput();

        file_put_contents($migrationPath, $migrationContent);

        return $migrationPath;
    }

    protected function getMigrationOutput()
    {
        $stubPath = __DIR__ . '/database/migrations/create_storage_table.php.stub';
        $migrationContent = file_get_contents($stubPath);

        $tableName = $this->app['config']['storage-database.disks.database.table'];

        $migrationContent = str_replace('{{ table }}', $tableName, $migrationContent);
        return $migrationContent;
    }
}
