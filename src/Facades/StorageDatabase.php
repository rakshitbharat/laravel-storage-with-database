<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool put(string $path, string $contents, array $options = [])
 * @method static string|null get(string $path)
 * @method static bool exists(string $path)
 * @method static bool delete(string $path)
 * @method static bool putMany(array $files)
 * @method static array getMany(array $paths)
 * @method static array existsMany(array $paths)
 * @method static bool deleteMany(array $paths)
 * @method static array getMonitoringStats(?string $operation = null)
 * @method static void resetMonitoring()
 * @method static array files(string $directory)
 * @method static array allFiles(string $directory)
 * @method static array directories(string $directory)
 * @method static array allDirectories(string $directory)
 */
class StorageDatabase extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'storage-database';
    }
}
