<?php

namespace Rakshitbharat\LaravelStorageWithDatabase;

use Illuminate\Support\Manager;

class StorageDatabaseManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('storage-database.default');
    }

    public function createDatabaseDriver()
    {
        $config = $this->config->get('storage-database.disks.database');

        return new DatabaseDriver($config);
    }

    public function exists($key)
    {
        return $this->driver()->exists($key);
    }

    public function get($key)
    {
        return $this->driver()->get($key);
    }

    public function getVisibility($key)
    {
        return $this->driver()->getVisibility($key);
    }

    public function put($key, $value, $options = [])
    {
        $this->driver()->put($key, $value, $options);
    }

    public function prepend($key, $value)
    {
        $this->driver()->prepend($key, $value);
    }

    public function append($key, $value)
    {
        $this->driver()->append($key, $value);
    }

    public function delete($key)
    {
        $this->driver()->delete($key);
    }

    public function copy($from, $to)
    {
        $this->driver()->copy($from, $to);
    }

    public function move($from, $to)
    {
        $this->driver()->move($from, $to);
    }

    public function size($key)
    {
        return $this->driver()->size($key);
    }

    public function lastModified($key)
    {
        return $this->driver()->lastModified($key);
    }

    public function url($key)
    {
        return $this->driver()->url($key);
    }

    public function temporaryUrl($key, $expiration, $options = [])
    {
        return $this->driver()->temporaryUrl($key, $expiration, $options);
    }

    public function getVisibility($key)
    {
        return $this->driver()->getVisibility($key);
    }

    public function setVisibility($key, $visibility)
    {
        $this->driver()->setVisibility($key, $visibility);
    }

    public function deleteDirectory($directory)
    {
        $this->driver()->deleteDirectory($directory);
    }

    public function files($directory)
    {
        return $this->driver()->files($directory);
    }

    public function allFiles($directory)
    {
        return $this->driver()->allFiles($directory);
    }

    public function directories($directory)
    {
        return $this->driver()->directories($directory);
    }

    public function allDirectories($directory)
    {
        return $this->driver()->allDirectories($directory);
    }

    public function makeDirectory($directory)
    {
        $this->driver()->makeDirectory($directory);
    }
}
