<?php

namespace Rakshitbharat\LaravelStorageWithDatabase;

use Illuminate\Support\Facades\DB;

class DatabaseDriver
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function exists($key)
    {
        return DB::connection($this->config['connection'])->table($this->config['table'])
            ->where('key', $key)
            ->exists();
    }

    public function get($key)
    {
        return DB::connection($this->config['connection'])->table($this->config['table'])
            ->where('key', $key)
            ->value('value');
    }

    public function getVisibility($key)
    {
        return 'public';
    }

    public function put($key, $value, $options = [])
    {
        DB::connection($this->config['connection'])->table($this->config['table'])->updateOrInsert(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public function prepend($key, $value)
    {
        $existingValue = $this->get($key);
        $newValue = $value . $existingValue;
        $this->put($key, $newValue);
    }

    public function append($key, $value)
    {
        $existingValue = $this->get($key);
        $newValue = $existingValue . $value;
        $this->put($key, $newValue);
    }

    public function delete($key)
    {
        DB::connection($this->config['connection'])->table($this->config['table'])
            ->where('key', $key)
            ->delete();
    }

    public function copy($from, $to)
    {
        $value = $this->get($from);
        $this->put($to, $value);
    }

    public function move($from, $to)
    {
        $value = $this->get($from);
        $this->put($to, $value);
        $this->delete($from);
    }

    public function size($key)
    {
        return strlen($this->get($key));
    }

    public function lastModified($key)
    {
        return time();
    }

    public function url($key)
    {
        return null;
    }

    public function temporaryUrl($key, $expiration, $options = [])
    {
        return null;
    }

    public function getVisibility($key)
    {
        return 'public';
    }

    public function setVisibility($key, $visibility)
    {
        // Do nothing since visibility is not supported
    }

    public function deleteDirectory($directory)
    {
        // Do nothing since directories are not supported
    }

    public function files($directory)
    {
        return [];
    }

    public function allFiles($directory)
    {
        return [];
    }

    public function directories($directory)
    {
        return [];
    }

    public function allDirectories($directory)
    {
        return [];
    }

    public function makeDirectory($directory)
    {
        // Do nothing since directories are not supported
    }
}
