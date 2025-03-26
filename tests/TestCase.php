<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Rakshitbharat\LaravelStorageWithDatabase\StorageDatabaseServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            StorageDatabaseServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../src/database/migrations');
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('storage-database.default', 'database');
        $app['config']->set('storage-database.run_migrations', true);
        $app['config']->set('storage-database.disks.database', [
            'driver' => 'database',
            'table' => 'storage',
            'connection' => 'testing',
        ]);
    }
}