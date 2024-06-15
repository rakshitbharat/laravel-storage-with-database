<?php

namespace Rakshitbharat\LaravelStorageWithDatabase;

use Illuminate\Support\ServiceProvider;

class StorageDatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('storage-database', function ($app) {
            return new StorageDatabaseManager($app);
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/storage-database.php' => config_path('storage-database.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/storage-database.php' => config_path('storage-database.php'),
            ], 'config');

            $this->publishes([
                $this->getMigrationFileName() => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_storage_table.php'),
            ], 'migrations');
        }
    }

    protected function getMigrationFileName()
    {
        $stubPath = __DIR__ . '/../database/migrations/create_storage_table.php.stub';
        $migrationContent = file_get_contents($stubPath);

        $tableName = $this->app['config']['storage-database.disks.database.table'];
        $migrationContent = str_replace('{{ table }}', $tableName, $migrationContent);

        return $migrationContent;
    }
}
