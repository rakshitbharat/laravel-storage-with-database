<?php

namespace Rakshitbharat\LaravelStorageWithDatabase;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Rakshitbharat\LaravelStorageWithDatabase\Cache\DirectoryCache;
use Rakshitbharat\LaravelStorageWithDatabase\Monitoring\StorageMonitor;
use Rakshitbharat\LaravelStorageWithDatabase\Exceptions\StorageDatabaseException;

class DatabaseDriver
{
    protected array $config;
    protected string $connection;
    protected string $table;
    protected string $contentColumn = 'value'; // Match with migration
    protected ?DirectoryCache $directoryCache = null;
    protected ?StorageMonitor $monitor = null;

    public function __construct(array $config)
    {
        if (!isset($config['disks']['database'])) {
            throw StorageDatabaseException::invalidConfiguration('Database disk configuration is missing');
        }

        $this->config = $config;
        $this->connection = $this->config['disks']['database']['connection'] ?? config('database.default');
        $this->table = $this->config['disks']['database']['table'] ?? 'storage';

        if ($this->shouldCache()) {
            $this->directoryCache = new DirectoryCache($config);
        }

        if ($this->shouldMonitor()) {
            $this->monitor = new StorageMonitor($config);
        }
    }

    protected function shouldCache(): bool
    {
        return $this->config['cache']['enabled'] ?? false;
    }

    protected function shouldMonitor(): bool
    {
        return $this->config['logging']['enabled'] ?? false;
    }

    public function exists($path)
    {
        $this->validatePath($path);
        $startTime = microtime(true);
        
        $exists = DB::connection($this->connection)
            ->table($this->table)
            ->where('path', $path)
            ->exists();

        if ($this->monitor) {
            $this->monitor->recordOperation('exists', $path);
        }

        return $exists;
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

        if ($this->monitor) {
            $this->monitor->recordOperation('get', $path, strlen($entry->{$this->contentColumn}));
        }

        return $entry->{$this->contentColumn};
    }

    public function put($path, $contents, $options = [])
    {
        try {
            $this->validatePath($path);
            $this->validateContent($contents);
            
            $data = [
                'path' => $path,
                $this->contentColumn => $contents,
                'updated_at' => now(),
                'size' => strlen($contents),
                'mime_type' => $this->getMimeType($contents),
                'checksum' => md5($contents),
            ];

            if (!$this->exists($path)) {
                $data['created_at'] = now();
            }

            $result = DB::connection($this->connection)
                ->table($this->table)
                ->updateOrInsert(
                    ['path' => $path],
                    $data
                );

            if ($this->directoryCache) {
                $this->directoryCache->invalidate($path);
            }

            if ($this->monitor) {
                $this->monitor->recordOperation('put', $path, strlen($contents));
            }

            return $result;
        } catch (\Exception $e) {
            if ($e instanceof StorageDatabaseException) {
                throw $e;
            }
            throw StorageDatabaseException::databaseError('put', $e);
        }
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
        $result = DB::connection($this->connection)
            ->table($this->table)
            ->where('path', $path)
            ->delete();

        if ($this->directoryCache) {
            $this->directoryCache->invalidate($path);
        }

        return $result;
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
        if ($this->directoryCache) {
            $result = $this->directoryCache->getFiles($directory);
            if ($this->monitor) {
                $this->monitor->recordOperation('files', $directory);
            }
            return $result;
        }

        $prefix = $directory === '/' ? '' : $directory . '/';
        
        $result = DB::connection($this->connection)
            ->table($this->table)
            ->where('path', 'like', $prefix . '%')
            ->where('path', 'not like', $prefix . '%/%')
            ->orderBy('path')
            ->pluck('path')
            ->toArray();

        if ($this->monitor) {
            $this->monitor->recordOperation('files', $directory);
        }

        return $result;
    }

    public function allFiles($directory)
    {
        if ($this->directoryCache) {
            return $this->directoryCache->getAllFiles($directory);
        }

        $prefix = $directory === '/' ? '' : $directory . '/';
        
        return DB::connection($this->connection)
            ->table($this->table)
            ->where('path', 'like', $prefix . '%')
            ->orderBy('path')
            ->pluck('path')
            ->toArray();
    }

    public function directories($directory)
    {
        $prefix = $directory === '/' ? '' : $directory . '/';
        $paths = DB::connection($this->connection)
            ->table($this->table)
            ->where('path', 'like', $prefix . '%')
            ->pluck('path')
            ->toArray();

        $directories = [];
        foreach ($paths as $path) {
            $relPath = substr($path, strlen($prefix));
            $parts = explode('/', $relPath);
            if (count($parts) > 1) {
                $directories[] = $prefix . $parts[0];
            }
        }

        return array_unique($directories);
    }

    public function allDirectories($directory)
    {
        $prefix = $directory === '/' ? '' : $directory . '/';
        $paths = DB::connection($this->connection)
            ->table($this->table)
            ->where('path', 'like', $prefix . '%')
            ->pluck('path')
            ->toArray();

        $directories = [];
        foreach ($paths as $path) {
            $relPath = substr($path, strlen($prefix));
            $parts = explode('/', $relPath);
            $currentPath = $prefix;
            for ($i = 0; $i < count($parts) - 1; $i++) {
                $currentPath .= $parts[$i] . '/';
                $directories[] = rtrim($currentPath, '/');
            }
        }

        return array_unique($directories);
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
            throw StorageDatabaseException::invalidConfiguration('Path cannot be empty');
        }

        if (!is_string($path)) {
            throw StorageDatabaseException::invalidConfiguration('Path must be a string');
        }

        $maxLength = $this->config['security']['max_key_length'] ?? 255;
        if (strlen($path) > $maxLength) {
            throw StorageDatabaseException::pathTooLong($path, $maxLength);
        }
    }

    protected function validateContent($contents)
    {
        $maxSize = $this->config['disks']['database']['max_content_size'] ?? 10485760;
        if (strlen($contents) > $maxSize) {
            throw StorageDatabaseException::contentTooLarge(strlen($contents), $maxSize);
        }

        $mimeType = $this->getMimeType($contents);
        $allowedTypes = $this->config['security']['allowed_mime_types'] ?? [];
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            throw StorageDatabaseException::invalidMimeType($mimeType, $allowedTypes);
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

    protected function getMimeType($contents)
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($contents);
    }

    public function getMonitor(): ?StorageMonitor
    {
        return $this->monitor;
    }

    public function putMany(array $files): bool
    {
        try {
            $timestamp = now();
            $failedItems = [];

            $records = Collection::make($files)->map(function ($file) use ($timestamp, &$failedItems) {
                try {
                    $this->validatePath($file['path']);
                    $this->validateContent($file['contents']);
                    
                    return [
                        'path' => $file['path'],
                        $this->contentColumn => $file['contents'],
                        'updated_at' => $timestamp,
                        'size' => strlen($file['contents']),
                        'mime_type' => $this->getMimeType($file['contents']),
                        'checksum' => md5($file['contents']),
                        'created_at' => $timestamp,
                    ];
                } catch (\Exception $e) {
                    $failedItems[] = [
                        'path' => $file['path'],
                        'error' => $e->getMessage(),
                    ];
                    return null;
                }
            })->filter();

            if (!empty($failedItems)) {
                throw StorageDatabaseException::batchOperationFailed('putMany', $failedItems);
            }

            DB::connection($this->connection)->transaction(function () use ($records) {
                foreach ($records->chunk(100) as $chunk) {
                    DB::connection($this->connection)
                        ->table($this->table)
                        ->upsert(
                            $chunk->toArray(),
                            ['path'],
                            [$this->contentColumn, 'updated_at', 'size', 'mime_type', 'checksum']
                        );
                }
            });

            if ($this->directoryCache) {
                foreach ($files as $file) {
                    $this->directoryCache->invalidate($file['path']);
                }
            }

            if ($this->monitor) {
                $this->monitor->recordOperation('putMany', 'batch', array_sum(array_map('strlen', array_column($files, 'contents'))));
            }

            return true;
        } catch (\Exception $e) {
            if ($e instanceof StorageDatabaseException) {
                throw $e;
            }
            throw StorageDatabaseException::databaseError('putMany', $e);
        }
    }

    public function deleteMany(array $paths): bool
    {
        if ($this->monitor) {
            $this->monitor->recordOperation('deleteMany', 'batch', count($paths));
        }

        DB::connection($this->connection)->transaction(function () use ($paths) {
            foreach (array_chunk($paths, 100) as $chunk) {
                DB::connection($this->connection)
                    ->table($this->table)
                    ->whereIn('path', $chunk)
                    ->delete();
            }
        });

        if ($this->directoryCache) {
            foreach ($paths as $path) {
                $this->directoryCache->invalidate($path);
            }
        }

        return true;
    }

    public function copyMany(array $files): bool
    {
        if ($this->monitor) {
            $this->monitor->recordOperation('copyMany', 'batch', count($files));
        }

        DB::connection($this->connection)->transaction(function () use ($files) {
            foreach ($files as $file) {
                $source = $file['from'];
                $destination = $file['to'];
                
                $content = DB::connection($this->connection)
                    ->table($this->table)
                    ->where('path', $source)
                    ->first();

                if ($content) {
                    $this->put($destination, $content->{$this->contentColumn});
                }
            }
        });

        return true;
    }

    public function getMany(array $paths): array
    {
        if ($this->monitor) {
            $this->monitor->recordOperation('getMany', 'batch', count($paths));
        }

        $results = DB::connection($this->connection)
            ->table($this->table)
            ->whereIn('path', $paths)
            ->get(['path', $this->contentColumn])
            ->keyBy('path')
            ->map(function ($item) {
                return $item->{$this->contentColumn};
            })
            ->all();

        // Check for missing files
        $missing = array_diff($paths, array_keys($results));
        if (!empty($missing)) {
            throw new FileNotFoundException("Files not found: " . implode(', ', $missing));
        }

        return $results;
    }

    public function existsMany(array $paths): array
    {
        if ($this->monitor) {
            $this->monitor->recordOperation('existsMany', 'batch', count($paths));
        }

        $existing = DB::connection($this->connection)
            ->table($this->table)
            ->whereIn('path', $paths)
            ->pluck('path')
            ->toArray();

        return array_map(function ($path) use ($existing) {
            return in_array($path, $existing);
        }, array_combine($paths, $paths));
    }
}
