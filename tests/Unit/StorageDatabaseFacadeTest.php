<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Tests\Unit;

use Rakshitbharat\LaravelStorageWithDatabase\Tests\TestCase;
use Rakshitbharat\LaravelStorageWithDatabase\Facades\StorageDatabase;
use Rakshitbharat\LaravelStorageWithDatabase\Exceptions\StorageDatabaseException;

class StorageDatabaseFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'storage-database.logging.enabled' => true,
            'storage-database.cache.enabled' => true,
        ]);
    }

    /** @test */
    public function it_provides_access_to_storage_operations()
    {
        StorageDatabase::put('test.txt', 'Hello World');
        
        $this->assertTrue(StorageDatabase::exists('test.txt'));
        $this->assertEquals('Hello World', StorageDatabase::get('test.txt'));
    }

    /** @test */
    public function it_provides_access_to_monitoring_features()
    {
        StorageDatabase::put('test.txt', 'Hello World');
        StorageDatabase::get('test.txt');
        
        $stats = StorageDatabase::getMonitoringStats();
        
        $this->assertArrayHasKey('put', $stats);
        $this->assertArrayHasKey('get', $stats);
        
        StorageDatabase::resetMonitoring();
        $newStats = StorageDatabase::getMonitoringStats();
        $this->assertEmpty($newStats);
    }

    /** @test */
    public function it_provides_access_to_batch_operations()
    {
        $files = [
            ['path' => 'file1.txt', 'contents' => 'Content 1'],
            ['path' => 'file2.txt', 'contents' => 'Content 2'],
        ];
        
        StorageDatabase::putMany($files);
        
        $contents = StorageDatabase::getMany(['file1.txt', 'file2.txt']);
        $this->assertEquals('Content 1', $contents['file1.txt']);
        $this->assertEquals('Content 2', $contents['file2.txt']);
    }

    /** @test */
    public function it_handles_directory_operations()
    {
        StorageDatabase::put('dir/file1.txt', 'Content 1');
        StorageDatabase::put('dir/subdir/file2.txt', 'Content 2');
        
        $files = StorageDatabase::files('dir');
        $allFiles = StorageDatabase::allFiles('dir');
        $directories = StorageDatabase::directories('dir');
        
        $this->assertContains('dir/file1.txt', $files);
        $this->assertContains('dir/subdir/file2.txt', $allFiles);
        $this->assertContains('dir/subdir', $directories);
    }

    /** @test */
    public function it_throws_monitoring_exception_when_disabled()
    {
        config(['storage-database.logging.enabled' => false]);
        
        $this->expectException(StorageDatabaseException::class);
        $this->expectExceptionMessage('Storage monitoring is not enabled in configuration');
        
        StorageDatabase::getMonitoringStats();
    }

    /** @test */
    public function it_handles_error_conditions()
    {
        $this->expectException(StorageDatabaseException::class);
        
        // Try to get a non-existent file
        StorageDatabase::get('nonexistent.txt');
    }

    /** @test */
    public function it_provides_access_to_raw_driver()
    {
        $driver = StorageDatabase::getDriver();
        
        $this->assertInstanceOf(\Rakshitbharat\LaravelStorageWithDatabase\DatabaseDriver::class, $driver);
    }

    /** @test */
    public function it_supports_filtering_monitoring_stats()
    {
        StorageDatabase::put('test1.txt', 'Content 1');
        StorageDatabase::put('test2.txt', 'Content 2');
        StorageDatabase::get('test1.txt');
        
        $putStats = StorageDatabase::getMonitoringStats('put');
        $this->assertEquals(2, $putStats['count']);
        
        $getStats = StorageDatabase::getMonitoringStats('get');
        $this->assertEquals(1, $getStats['count']);
    }
}