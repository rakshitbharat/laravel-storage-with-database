<?php

namespace Rakshitbharat\LaravelStorageWithDatabase;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DatabaseDriver
{
    protected array $config;
    protected string $connection;
    protected string $table;
    protected string $contentColumn = 'value'; // Match with migration

    public function __construct(array $config)
    {
        if (!isset($config['disks']['database'])) {
            throw new InvalidArgumentException('Database configuration is missing');
        }

        $this->config = $config['disks']['database'];
        $this->connection = $this->config['connection'] ?? config('database.default');
        $this->table = $this->config['table'] ?? 'storage';
    }

    public function exists($path)
    {
        $this->validatePath($path);
        return DB::connection($this->connection)
            ->table($this->table)
            ->where('path', $path)
            ->exists();
    }

    public function get($path)
    {
        $this->validatePath($path);
        $entry = DB::connection($this->connection)
            ->table($this->table)
            ->where('path', $path)
            ->first();

        if (!$entry) {
            throw new FileNotFoundException("File not found at path: {$path}");
        }

        return $entry->{$this->contentColumn};
    }

    public function put($path, $contents, $options = [])
    {
        $this->validatePath($path);
        
        $data = [
            'path' => $path,
            $this->contentColumn => $contents,
            'updated_at' => now(),
        ];

        if (!$this->exists($path)) {
            $data['created_at'] = now();
        }

        return DB::connection($this->connection)
            ->table($this->table)
            ->updateOrInsert(
                ['path' => $path],
                $data
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
        DB::connection($this->connection)->table($this->table)
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
        $entry = DB::connection($this->connection)
            ->table($this->table)
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

    protected function validatePath($path)
    {
        if (empty($path)) {
            throw new InvalidArgumentException('Path cannot be empty');
        }

        if (!is_string($path)) {
            throw new InvalidArgumentException('Path must be a string');
        }
    }

    public function url($path)
    {
        throw new \RuntimeException('URL generation is not supported for database storage.');
    }

    public function temporaryUrl($path, $expiration, $options = [])
    {
        throw new \RuntimeException('Temporary URLs are not supported for database storage.');
    }

    public function getVisibility($path)
    {
        return 'private';
    }

    public function setVisibility($path, $visibility)
    {
        return true; // Always private, no-op
    }
}
