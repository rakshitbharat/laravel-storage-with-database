<?php

namespace Rakshitbharat\LaravelStorageWithDatabase;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;

class DatabaseDriver
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config['disks']['database'];
    }

    public function exists($path)
    {
        return DB::connection($this->config['connection'])->table($this->config['table'])
            ->where('path', $path)
            ->exists();
    }

    public function get($path)
    {
        $entry = DB::connection($this->config['connection'])->table($this->config['table'])
            ->where('path', $path)
            ->first();

        if (!$entry) {
            throw new FileNotFoundException($path);
        }

        return $entry->contents;
    }

    public function put($path, $contents, $options = [])
    {
        DB::connection($this->config['connection'])->table($this->config['table'])->updateOrInsert(
            ['path' => $path],
            ['contents' => $contents]
        );
    }

    public function prepend($path, $contents)
    {
        $existingContents = $this->get($path);
        $newContents = $contents . $existingContents;
        $this->put($path, $newContents);
    }

    public function append($path, $contents)
    {
        $existingContents = $this->get($path);
        $newContents = $existingContents . $contents;
        $this->put($path, $newContents);
    }

    public function delete($path)
    {
        DB::connection($this->config['connection'])->table($this->config['table'])
            ->where('path', $path)
            ->delete();
    }

    public function copy($from, $to)
    {
        $contents = $this->get($from);
        $this->put($to, $contents);
    }

    public function move($from, $to)
    {
        $contents = $this->get($from);
        $this->put($to, $contents);
        $this->delete($from);
    }

    public function size($path)
    {
        $contents = $this->get($path);
        return strlen($contents);
    }

    public function lastModified($path)
    {
        $entry = DB::connection($this->config['connection'])->table($this->config['table'])
            ->where('path', $path)
            ->first();

        if (!$entry) {
            throw new FileNotFoundException($path);
        }

        return $entry->updated_at->timestamp;
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

    public function makeDirectory($path)
    {
        // Do nothing since directories are not supported
    }

    public function deleteDirectory($directory)
    {
        // Do nothing since directories are not supported
    }
}
