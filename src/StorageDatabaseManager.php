<?php

namespace Rakshitbharat\LaravelStorageWithDatabase;

use Illuminate\Support\Manager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class StorageDatabaseManager extends Manager
{
    protected function getDefaultDriver()
    {
        return $this->config->get('storage-database.default');
    }

    protected function createDatabaseDriver()
    {
        $config = $this->config->get('storage-database');
        return $this->wrapDriver(new DatabaseDriver($config));
    }

    protected function wrapDriver($driver)
    {
        if ($this->shouldCache()) {
            $driver = $this->addCaching($driver);
        }

        if ($this->shouldLog()) {
            $driver = $this->addLogging($driver);
        }

        if ($this->shouldValidateContent()) {
            $driver = $this->addContentValidation($driver);
        }

        return $driver;
    }

    protected function shouldCache(): bool
    {
        return $this->config->get('storage-database.cache.enabled', false);
    }

    protected function shouldLog(): bool
    {
        return $this->config->get('storage-database.logging.enabled', false);
    }

    protected function shouldValidateContent(): bool
    {
        return $this->config->get('storage-database.security.validate_content', true);
    }

    protected function addCaching($driver)
    {
        $ttl = $this->config->get('storage-database.cache.ttl', 3600);
        $store = $this->config->get('storage-database.cache.store');

        $methods = ['get', 'exists', 'size', 'lastModified'];

        foreach ($methods as $method) {
            $originalMethod = $driver->$method;
            $driver->$method = function ($path) use ($originalMethod, $ttl, $store) {
                $cacheKey = "storage_database:{$method}:{$path}";
                return Cache::store($store)->remember($cacheKey, $ttl, function () use ($originalMethod, $path) {
                    return $originalMethod($path);
                });
            };
        }

        // Automatically invalidate cache on write operations
        $invalidationMethods = ['put', 'delete', 'copy', 'move'];
        foreach ($invalidationMethods as $method) {
            $originalMethod = $driver->$method;
            $driver->$method = function (...$args) use ($originalMethod, $store) {
                $result = $originalMethod(...$args);
                $this->invalidateCache($args[0], $store);
                if ($method === 'copy' || $method === 'move') {
                    $this->invalidateCache($args[1], $store);
                }
                return $result;
            };
        }

        return $driver;
    }

    protected function addLogging($driver)
    {
        $channel = $this->config->get('storage-database.logging.channel');

        foreach (get_class_methods($driver) as $method) {
            if (method_exists($driver, $method)) {
                $originalMethod = $driver->$method;
                $driver->$method = function (...$args) use ($originalMethod, $method, $channel) {
                    try {
                        $result = $originalMethod(...$args);
                        Log::channel($channel)->info("Storage operation succeeded", [
                            'operation' => $method,
                            'arguments' => $args,
                        ]);
                        return $result;
                    } catch (\Exception $e) {
                        Log::channel($channel)->error("Storage operation failed", [
                            'operation' => $method,
                            'arguments' => $args,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e;
                    }
                };
            }
        }

        return $driver;
    }

    protected function addContentValidation($driver)
    {
        $maxSize = $this->config->get('storage-database.disks.database.max_content_size');
        $allowedMimes = $this->config->get('storage-database.security.allowed_mime_types', []);
        $maxKeyLength = $this->config->get('storage-database.security.max_key_length', 255);

        $originalPut = $driver->put;
        $driver->put = function ($path, $contents, $options = []) use ($originalPut, $maxSize, $allowedMimes, $maxKeyLength) {
            $this->validateContent($path, $contents, $maxSize, $allowedMimes, $maxKeyLength);
            return $originalPut($path, $contents, $options);
        };

        return $driver;
    }

    protected function validateContent($path, $contents, $maxSize, $allowedMimes, $maxKeyLength)
    {
        if (strlen($path) > $maxKeyLength) {
            throw new InvalidArgumentException("Path exceeds maximum length of {$maxKeyLength} characters");
        }

        if (strlen($contents) > $maxSize) {
            throw new InvalidArgumentException("Content size exceeds maximum of {$maxSize} bytes");
        }

        $mimeType = $this->getMimeType($contents);
        if (!empty($allowedMimes) && !in_array($mimeType, $allowedMimes)) {
            throw new InvalidArgumentException("MIME type {$mimeType} is not allowed");
        }
    }

    protected function getMimeType($contents)
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($contents);
    }

    protected function invalidateCache($path, $store)
    {
        $methods = ['get', 'exists', 'size', 'lastModified'];
        foreach ($methods as $method) {
            Cache::store($store)->forget("storage_database:{$method}:{$path}");
        }
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
