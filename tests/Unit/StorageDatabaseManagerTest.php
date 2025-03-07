<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Tests\Unit;

use Rakshitbharat\LaravelStorageWithDatabase\Tests\TestCase;
use Rakshitbharat\LaravelStorageWithDatabase\StorageDatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StorageDatabaseManagerTest extends TestCase
{
    protected StorageDatabaseManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(StorageDatabaseManager::class);
    }

    /** @test */
    public function it_enables_caching_when_configured()
    {
        config(['storage-database.cache.enabled' => true]);
        $driver = $this->manager->driver();
        
        $driver->put('test.txt', 'content');
        
        // Content should be cached
        $cacheKey = 'storage_database:get:test.txt';
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_logs_operations_when_configured()
    {
        config(['storage-database.logging.enabled' => true]);
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->once()->with(
            'Storage operation succeeded',
            \Mockery::hasKey('operation')
        );

        $this->manager->driver()->put('test.txt', 'content');
    }

    /** @test */
    public function it_validates_content_size_when_configured()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        config([
            'storage-database.security.validate_content' => true,
            'storage-database.disks.database.max_content_size' => 5
        ]);
        
        $this->manager->driver()->put('test.txt', 'content too long');
    }

    /** @test */
    public function it_respects_cache_ttl_setting()
    {
        config([
            'storage-database.cache.enabled' => true,
            'storage-database.cache.ttl' => 1 // 1 second
        ]);
        
        $driver = $this->manager->driver();
        $driver->put('test.txt', 'content');
        
        $this->assertTrue(Cache::has('storage_database:get:test.txt'));
        
        sleep(2); // Wait for cache to expire
        
        $this->assertFalse(Cache::has('storage_database:get:test.txt'));
    }

    /** @test */
    public function it_invalidates_cache_on_write_operations()
    {
        config(['storage-database.cache.enabled' => true]);
        
        $driver = $this->manager->driver();
        $driver->put('test.txt', 'initial');
        
        // Cache should exist after first write
        $this->assertTrue(Cache::has('storage_database:get:test.txt'));
        
        // Update content
        $driver->put('test.txt', 'updated');
        
        // Cache should be invalidated
        $this->assertFalse(Cache::has('storage_database:get:test.txt'));
    }
}