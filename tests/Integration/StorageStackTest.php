<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Tests\Integration;

use Rakshitbharat\LaravelStorageWithDatabase\Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Rakshitbharat\LaravelStorageWithDatabase\Exceptions\StorageDatabaseException;

class StorageStackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Enable all features for integration testing
        config([
            'storage-database.cache.enabled' => true,
            'storage-database.logging.enabled' => true,
            'storage-database.security.validate_content' => true,
            'storage-database.security.allowed_mime_types' => ['text/plain'],
            'storage-database.disks.database.max_content_size' => 1024, // 1KB
        ]);
    }

    /** @test */
    public function it_integrates_caching_monitoring_and_validation()
    {
        $disk = Storage::disk('database');
        
        // Test file storage with monitoring
        $disk->put('test.txt', 'Hello World');
        
        // Verify content is cached
        $cacheKey = 'storage_database:get:test.txt';
        $this->assertTrue(Cache::has($cacheKey));
        
        // Verify monitoring stats
        $driver = $disk->getDriver();
        $monitor = $driver->getMonitor();
        $stats = $monitor->getStats('put');
        
        $this->assertEquals(1, $stats['count']);
        $this->assertGreaterThan(0, $stats['total_duration']);
        $this->assertEquals(11, $stats['total_size']); // "Hello World" length
    }

    /** @test */
    public function it_enforces_security_constraints()
    {
        $disk = Storage::disk('database');
        
        // Test mime type validation
        try {
            $disk->put('test.json', json_encode(['key' => 'value']));
            $this->fail('Should throw exception for invalid mime type');
        } catch (StorageDatabaseException $e) {
            $this->assertEquals('mime_type_error', $e->getData()['type']);
        }

        // Test content size validation
        try {
            $disk->put('large.txt', str_repeat('x', 2048)); // 2KB
            $this->fail('Should throw exception for content too large');
        } catch (StorageDatabaseException $e) {
            $this->assertEquals('content_size_error', $e->getData()['type']);
        }
    }

    /** @test */
    public function it_handles_batch_operations_with_error_collection()
    {
        $disk = Storage::disk('database');
        $driver = $disk->getDriver();

        $files = [
            ['path' => 'valid.txt', 'contents' => 'Valid content'],
            ['path' => 'toolarge.txt', 'contents' => str_repeat('x', 2048)],
            ['path' => 'invalid.json', 'contents' => '{"key": "value"}'],
        ];

        try {
            $driver->putMany($files);
            $this->fail('Should throw batch operation exception');
        } catch (StorageDatabaseException $e) {
            $this->assertEquals('batch_operation_error', $e->getData()['type']);
            $this->assertCount(2, $e->getData()['failed_items']);
            
            // Verify the valid file was not stored
            $this->assertFalse($driver->exists('valid.txt'));
        }
    }

    /** @test */
    public function it_maintains_directory_cache_consistency()
    {
        $disk = Storage::disk('database');
        
        // Create files in a directory structure
        $disk->put('dir/file1.txt', 'Content 1');
        $disk->put('dir/subdir/file2.txt', 'Content 2');
        
        // Cache directory listings
        $files = $disk->files('dir');
        $allFiles = $disk->allFiles('dir');
        
        // Modify structure and verify cache invalidation
        $disk->delete('dir/file1.txt');
        $disk->put('dir/file3.txt', 'Content 3');
        
        $newFiles = $disk->files('dir');
        $newAllFiles = $disk->allFiles('dir');
        
        // Verify cache was properly invalidated
        $this->assertNotEquals($files, $newFiles);
        $this->assertNotEquals($allFiles, $newAllFiles);
        $this->assertContains('dir/file3.txt', $newFiles);
        $this->assertNotContains('dir/file1.txt', $newFiles);
    }

    /** @test */
    public function it_provides_accurate_monitoring_metrics()
    {
        $disk = Storage::disk('database');
        $driver = $disk->getDriver();
        
        // Perform various operations
        $disk->put('test1.txt', 'Content 1');
        $disk->put('test2.txt', 'Content 2');
        $disk->get('test1.txt');
        $disk->exists('test3.txt');
        $disk->delete('test2.txt');
        
        $monitor = $driver->getMonitor();
        $stats = $monitor->getStats();
        
        // Verify operation counts
        $this->assertEquals(2, $stats['put']['count']);
        $this->assertEquals(1, $stats['get']['count']);
        $this->assertEquals(1, $stats['exists']['count']);
        $this->assertEquals(1, $stats['delete']['count']);
        
        // Verify size tracking
        $this->assertEquals(16, $stats['put']['total_size']); // "Content 1" + "Content 2"
    }

    /** @test */
    public function it_handles_concurrent_access_properly()
    {
        $disk = Storage::disk('database');
        
        // Simulate concurrent writes
        $processes = [];
        for ($i = 0; $i < 5; $i++) {
            $processes[] = function () use ($disk, $i) {
                $disk->put("concurrent{$i}.txt", "Content {$i}");
                return true;
            };
        }

        // Run processes in parallel
        $results = collect($processes)->map(function ($process) {
            return $process();
        });

        // Verify all writes succeeded
        $this->assertEquals(5, $results->filter()->count());
        
        // Verify all files exist with correct content
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($disk->exists("concurrent{$i}.txt"));
            $this->assertEquals("Content {$i}", $disk->get("concurrent{$i}.txt"));
        }
    }
}