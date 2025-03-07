<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DirectoryCache
{
    protected string $cacheStore;
    protected int $ttl;
    protected string $table;
    protected string $connection;

    public function __construct(array $config)
    {
        $this->cacheStore = $config['cache']['store'] ?? 'file';
        $this->ttl = $config['cache']['ttl'] ?? 3600;
        $this->table = $config['disks']['database']['table'];
        $this->connection = $config['disks']['database']['connection'];
    }

    public function getFiles(string $directory): array
    {
        $cacheKey = "storage_database:files:{$directory}";
        
        return Cache::store($this->cacheStore)->remember($cacheKey, $this->ttl, function () use ($directory) {
            return $this->queryFiles($directory);
        });
    }

    public function getAllFiles(string $directory): array
    {
        $cacheKey = "storage_database:all_files:{$directory}";
        
        return Cache::store($this->cacheStore)->remember($cacheKey, $this->ttl, function () use ($directory) {
            return $this->queryAllFiles($directory);
        });
    }

    public function invalidate(string $path): void
    {
        $directory = dirname($path);
        Cache::store($this->cacheStore)->forget("storage_database:files:{$directory}");
        Cache::store($this->cacheStore)->forget("storage_database:all_files:{$directory}");
        
        // Also invalidate parent directories
        while ($directory !== '.' && $directory !== '/') {
            $directory = dirname($directory);
            Cache::store($this->cacheStore)->forget("storage_database:files:{$directory}");
            Cache::store($this->cacheStore)->forget("storage_database:all_files:{$directory}");
        }
    }

    protected function queryFiles(string $directory): array
    {
        $prefix = $directory === '/' ? '' : $directory . '/';
        
        return DB::connection($this->connection)
            ->table($this->table)
            ->where('path', 'like', $prefix . '%')
            ->where('path', 'not like', $prefix . '%/%')
            ->orderBy('path')
            ->pluck('path')
            ->toArray();
    }

    protected function queryAllFiles(string $directory): array
    {
        $prefix = $directory === '/' ? '' : $directory . '/';
        
        return DB::connection($this->connection)
            ->table($this->table)
            ->where('path', 'like', $prefix . '%')
            ->orderBy('path')
            ->pluck('path')
            ->toArray();
    }
}