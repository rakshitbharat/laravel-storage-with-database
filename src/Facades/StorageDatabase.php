<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Facades;

use Illuminate\Support\Facades\Facade;

class StorageDatabase extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'storage-database';
    }

    public static function exists($key)
    {
        return static::$app['storage-database']->exists($key);
    }

    public static function get($key)
    {
        return static::$app['storage-database']->get($key);
    }

    public static function getVisibility($key)
    {
        return static::$app['storage-database']->getVisibility($key);
    }

    public static function put($key, $value, $options = [])
    {
        static::$app['storage-database']->put($key, $value, $options);
    }

    public static function prepend($key, $value)
    {
        static::$app['storage-database']->prepend($key, $value);
    }

    public static function append($key, $value)
    {
        static::$app['storage-database']->append($key, $value);
    }

    public static function delete($key)
    {
        static::$app['storage-database']->delete($key);
    }

    public static function copy($from, $to)
    {
        static::$app['storage-database']->copy($from, $to);
    }

    public static function move($from, $to)
    {
        static::$app['storage-database']->move($from, $to);
    }

    public static function size($key)
    {
        return static::$app['storage-database']->size($key);
    }

    public static function lastModified($key)
    {
        return static::$app['storage-database']->lastModified($key);
    }

    public static function url($key)
    {
        return static::$app['storage-database']->url($key);
    }

    public static function temporaryUrl($key, $expiration, $options = [])
    {
        return static::$app['storage-database']->temporaryUrl($key, $expiration, $options);
    }

    public static function getVisibility($key)
    {
        return static::$app['storage-database']->getVisibility($key);
    }

    public static function setVisibility($key, $visibility)
    {
        static::$app['storage-database']->setVisibility($key, $visibility);
    }

    public static function deleteDirectory($directory)
    {
        static::$app['storage-database']->deleteDirectory($directory);
    }

    public static function files($directory)
    {
        return static::$app['storage-database']->files($directory);
    }

    public static function allFiles($directory)
    {
        return static::$app['storage-database']->allFiles($directory);
    }

    public static function directories($directory)
    {
        return static::$app['storage-database']->directories($directory);
    }

    public static function allDirectories($directory)
    {
        return static::$app['storage-database']->allDirectories($directory);
    }

    public static function makeDirectory($directory)
    {
        static::$app['storage-database']->makeDirectory($directory);
    }
}
