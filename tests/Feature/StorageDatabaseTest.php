<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Rakshitbharat\LaravelStorageWithDatabase\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class StorageDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['storage-database.cache.enabled' => true]);
    }

    /** @test */
    public function it_can_store_and_retrieve_data()
    {
        Storage::disk('database')->put('test.txt', 'Hello World');
        $this->assertEquals('Hello World', Storage::disk('database')->get('test.txt'));
    }

    /** @test */
    public function it_can_check_if_file_exists()
    {
        Storage::disk('database')->put('test.txt', 'content');
        $this->assertTrue(Storage::disk('database')->exists('test.txt'));
        $this->assertFalse(Storage::disk('database')->exists('nonexistent.txt'));
    }

    /** @test */
    public function it_can_delete_files()
    {
        Storage::disk('database')->put('test.txt', 'content');
        Storage::disk('database')->delete('test.txt');
        $this->assertFalse(Storage::disk('database')->exists('test.txt'));
    }

    /** @test */
    public function it_can_copy_files()
    {
        Storage::disk('database')->put('source.txt', 'content');
        Storage::disk('database')->copy('source.txt', 'destination.txt');
        
        $this->assertTrue(Storage::disk('database')->exists('source.txt'));
        $this->assertTrue(Storage::disk('database')->exists('destination.txt'));
        $this->assertEquals('content', Storage::disk('database')->get('destination.txt'));
    }

    /** @test */
    public function it_can_move_files()
    {
        Storage::disk('database')->put('source.txt', 'content');
        Storage::disk('database')->move('source.txt', 'destination.txt');
        
        $this->assertFalse(Storage::disk('database')->exists('source.txt'));
        $this->assertTrue(Storage::disk('database')->exists('destination.txt'));
        $this->assertEquals('content', Storage::disk('database')->get('destination.txt'));
    }

    /** @test */
    public function it_caches_file_contents()
    {
        Storage::disk('database')->put('test.txt', 'cached content');
        
        // First read should cache
        $content = Storage::disk('database')->get('test.txt');
        $this->assertEquals('cached content', $content);
        
        // Manually update in database to verify cache is working
        \DB::table('storage')->where('path', 'test.txt')
            ->update(['value' => 'updated content']);
            
        // Should still return cached content
        $this->assertEquals('cached content', Storage::disk('database')->get('test.txt'));
        
        // Clear cache and verify we get new content
        Cache::flush();
        $this->assertEquals('updated content', Storage::disk('database')->get('test.txt'));
    }

    /** @test */
    public function it_validates_content_size()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        config(['storage-database.disks.database.max_content_size' => 5]);
        Storage::disk('database')->put('test.txt', 'content too long');
    }

    /** @test */
    public function it_validates_mime_types()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        config(['storage-database.security.allowed_mime_types' => ['text/plain']]);
        Storage::disk('database')->put('test.json', json_encode(['key' => 'value']));
    }

    /** @test */
    public function it_handles_append_and_prepend()
    {
        Storage::disk('database')->put('test.txt', 'middle');
        Storage::disk('database')->prepend('test.txt', 'start ');
        Storage::disk('database')->append('test.txt', ' end');
        
        $this->assertEquals('start middle end', Storage::disk('database')->get('test.txt'));
    }

    /** @test */
    public function it_returns_correct_file_size()
    {
        $content = 'Hello World';
        Storage::disk('database')->put('test.txt', $content);
        
        $this->assertEquals(strlen($content), Storage::disk('database')->size('test.txt'));
    }

    /** @test */
    public function it_tracks_last_modified_time()
    {
        Storage::disk('database')->put('test.txt', 'content');
        $time = time();
        
        $lastModified = Storage::disk('database')->lastModified('test.txt');
        
        $this->assertGreaterThanOrEqual($time - 1, $lastModified);
        $this->assertLessThanOrEqual($time + 1, $lastModified);
    }
}