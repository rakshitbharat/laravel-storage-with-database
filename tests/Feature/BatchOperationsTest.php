<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Tests\Feature;

use Rakshitbharat\LaravelStorageWithDatabase\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class BatchOperationsTest extends TestCase
{
    /** @test */
    public function it_can_store_multiple_files_at_once()
    {
        $files = [
            ['path' => 'test1.txt', 'contents' => 'Content 1'],
            ['path' => 'test2.txt', 'contents' => 'Content 2'],
            ['path' => 'test3.txt', 'contents' => 'Content 3'],
        ];

        $driver = Storage::disk('database')->getDriver();
        $result = $driver->putMany($files);

        $this->assertTrue($result);
        foreach ($files as $file) {
            $this->assertTrue($driver->exists($file['path']));
            $this->assertEquals($file['contents'], $driver->get($file['path']));
        }
    }

    /** @test */
    public function it_can_delete_multiple_files_at_once()
    {
        $files = [
            ['path' => 'test1.txt', 'contents' => 'Content 1'],
            ['path' => 'test2.txt', 'contents' => 'Content 2'],
            ['path' => 'test3.txt', 'contents' => 'Content 3'],
        ];

        $driver = Storage::disk('database')->getDriver();
        $driver->putMany($files);

        $paths = array_column($files, 'path');
        $result = $driver->deleteMany($paths);

        $this->assertTrue($result);
        foreach ($paths as $path) {
            $this->assertFalse($driver->exists($path));
        }
    }

    /** @test */
    public function it_can_copy_multiple_files_at_once()
    {
        $files = [
            ['path' => 'source1.txt', 'contents' => 'Content 1'],
            ['path' => 'source2.txt', 'contents' => 'Content 2'],
        ];

        $driver = Storage::disk('database')->getDriver();
        $driver->putMany($files);

        $copyOperations = [
            ['from' => 'source1.txt', 'to' => 'dest1.txt'],
            ['from' => 'source2.txt', 'to' => 'dest2.txt'],
        ];

        $result = $driver->copyMany($copyOperations);

        $this->assertTrue($result);
        foreach ($copyOperations as $op) {
            $this->assertTrue($driver->exists($op['from']));
            $this->assertTrue($driver->exists($op['to']));
            $this->assertEquals(
                $driver->get($op['from']),
                $driver->get($op['to'])
            );
        }
    }

    /** @test */
    public function it_can_retrieve_multiple_files_at_once()
    {
        $files = [
            ['path' => 'test1.txt', 'contents' => 'Content 1'],
            ['path' => 'test2.txt', 'contents' => 'Content 2'],
        ];

        $driver = Storage::disk('database')->getDriver();
        $driver->putMany($files);

        $paths = array_column($files, 'path');
        $contents = $driver->getMany($paths);

        $this->assertCount(2, $contents);
        foreach ($files as $file) {
            $this->assertArrayHasKey($file['path'], $contents);
            $this->assertEquals($file['contents'], $contents[$file['path']]);
        }
    }

    /** @test */
    public function it_throws_exception_for_missing_files_in_get_many()
    {
        $this->expectException(FileNotFoundException::class);

        $driver = Storage::disk('database')->getDriver();
        $driver->getMany(['nonexistent1.txt', 'nonexistent2.txt']);
    }

    /** @test */
    public function it_can_check_existence_of_multiple_files()
    {
        $files = [
            ['path' => 'test1.txt', 'contents' => 'Content 1'],
            ['path' => 'test2.txt', 'contents' => 'Content 2'],
        ];

        $driver = Storage::disk('database')->getDriver();
        $driver->putMany($files);

        $paths = array_merge(
            array_column($files, 'path'),
            ['nonexistent.txt']
        );

        $exists = $driver->existsMany($paths);

        $this->assertTrue($exists['test1.txt']);
        $this->assertTrue($exists['test2.txt']);
        $this->assertFalse($exists['nonexistent.txt']);
    }

    /** @test */
    public function it_handles_large_batch_operations()
    {
        $files = array_map(function ($i) {
            return [
                'path' => "file{$i}.txt",
                'contents' => "Content {$i}"
            ];
        }, range(1, 150)); // Test with more than our chunk size

        $driver = Storage::disk('database')->getDriver();
        $result = $driver->putMany($files);

        $this->assertTrue($result);
        
        // Verify all files were stored
        $paths = array_column($files, 'path');
        $exists = $driver->existsMany($paths);
        
        foreach ($paths as $path) {
            $this->assertTrue($exists[$path]);
        }

        // Test batch deletion
        $result = $driver->deleteMany($paths);
        $this->assertTrue($result);

        // Verify all files were deleted
        $exists = $driver->existsMany($paths);
        foreach ($paths as $path) {
            $this->assertFalse($exists[$path]);
        }
    }
}