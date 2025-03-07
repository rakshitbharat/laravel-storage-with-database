<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Tests\Unit;

use Rakshitbharat\LaravelStorageWithDatabase\Tests\TestCase;
use Rakshitbharat\LaravelStorageWithDatabase\Cache\DirectoryCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DirectoryCacheTest extends TestCase
{
    protected DirectoryCache $cache;
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = [
            'cache' => [
                'enabled' => true,
                'store' => 'array',
                'ttl' => 3600,
            ],
            'disks' => [
                'database' => [
                    'table' => 'storage',
                    'connection' => 'testing',
                ],
            ],
        ];

        $this->cache = new DirectoryCache($this->config);
    }

    /** @test */
    public function it_caches_directory_listings()
    {
        // Create some test files
        DB::table('storage')->insert([
            ['path' => 'test/file1.txt', 'value' => 'content1'],
            ['path' => 'test/file2.txt', 'value' => 'content2'],
            ['path' => 'test/subdir/file3.txt', 'value' => 'content3'],
        ]);

        // First call should cache
        $files = $this->cache->getFiles('test');
        $this->assertCount(2, $files);
        $this->assertContains('test/file1.txt', $files);
        $this->assertContains('test/file2.txt', $files);

        // Add a new file - should not appear due to cache
        DB::table('storage')->insert([
            ['path' => 'test/file4.txt', 'value' => 'content4'],
        ]);

        $cachedFiles = $this->cache->getFiles('test');
        $this->assertCount(2, $cachedFiles);
        $this->assertNotContains('test/file4.txt', $cachedFiles);

        // Invalidate cache
        $this->cache->invalidate('test/file4.txt');

        // Should now see all files
        $freshFiles = $this->cache->getFiles('test');
        $this->assertCount(3, $freshFiles);
        $this->assertContains('test/file4.txt', $freshFiles);
    }

    /** @test */
    public function it_caches_recursive_directory_listings()
    {
        // Create test files in nested directories
        DB::table('storage')->insert([
            ['path' => 'test/file1.txt', 'value' => 'content1'],
            ['path' => 'test/subdir/file2.txt', 'value' => 'content2'],
            ['path' => 'test/subdir/nested/file3.txt', 'value' => 'content3'],
        ]);

        // First call should cache
        $files = $this->cache->getAllFiles('test');
        $this->assertCount(3, $files);

        // Add a new file - should not appear due to cache
        DB::table('storage')->insert([
            ['path' => 'test/subdir/file4.txt', 'value' => 'content4'],
        ]);

        $cachedFiles = $this->cache->getAllFiles('test');
        $this->assertCount(3, $cachedFiles);
        $this->assertNotContains('test/subdir/file4.txt', $cachedFiles);

        // Invalidate cache for parent directory
        $this->cache->invalidate('test/subdir/file4.txt');

        // Should now see all files
        $freshFiles = $this->cache->getAllFiles('test');
        $this->assertCount(4, $freshFiles);
        $this->assertContains('test/subdir/file4.txt', $freshFiles);
    }

    /** @test */
    public function it_invalidates_parent_directory_caches()
    {
        DB::table('storage')->insert([
            ['path' => 'parent/child/grandchild/file.txt', 'value' => 'content'],
        ]);

        // Cache all directory levels
        $this->cache->getFiles('parent');
        $this->cache->getFiles('parent/child');
        $this->cache->getFiles('parent/child/grandchild');

        // Add new file and invalidate cache
        DB::table('storage')->insert([
            ['path' => 'parent/child/grandchild/new.txt', 'value' => 'new content'],
        ]);
        $this->cache->invalidate('parent/child/grandchild/new.txt');

        // All parent directory caches should be invalidated
        $parentFiles = $this->cache->getFiles('parent');
        $childFiles = $this->cache->getFiles('parent/child');
        $grandchildFiles = $this->cache->getFiles('parent/child/grandchild');

        $this->assertContains('parent/child/grandchild/new.txt', $grandchildFiles);
        $this->assertCount(2, $grandchildFiles);
    }
}