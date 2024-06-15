<?php

namespace Rakshitbharat\LaravelStorageWithDatabase;

use Illuminate\Support\ServiceProvider;

class StorageDatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/storage-database.php', 'storage-database'
        );

        $this->app->singleton('storage-database', function ($app) {
            return new StorageDatabaseManager($app);
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/storage-database.php' => config_path('storage-database.php'),
            ], 'config');

            $this->publishes([
                $this->getMigrationFilePath() => $this->getMigrationOutputPath(),
            ], 'migrations');
        }
    }

    protected function getMigrationFilePath()
    {
        return __DIR__ . '/database/migrations/create_storage_table.php.stub';
    }

    protected function getMigrationOutputPath()
    {
        return database_path('migrations/' . date('Y_m_d_His') . '_create_storage_table.php');
    }
}
