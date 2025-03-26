<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Tests\Unit;

use Rakshitbharat\LaravelStorageWithDatabase\Tests\TestCase;
use Rakshitbharat\LaravelStorageWithDatabase\DatabaseDriver;
use InvalidArgumentException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class DatabaseDriverTest extends TestCase
{
    protected DatabaseDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('storage-database');
        $this->driver = new DatabaseDriver($config);
    }

    /** @test */
    public function it_requires_valid_config()
    {
        $this->expectException(InvalidArgumentException::class);
        new DatabaseDriver([]);
    }

    /** @test */
    public function it_validates_path()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->driver->get('');
    }

    /** @test */
    public function it_throws_not_found_exception()
    {
        $this->expectException(FileNotFoundException::class);
        $this->driver->get('nonexistent.txt');
    }

    /** @test */
    public function it_calculates_correct_size()
    {
        $content = str_repeat('a', 1000);
        $this->driver->put('test.txt', $content);
        $this->assertEquals(1000, $this->driver->size('test.txt'));
    }

    /** @test */
    public function it_updates_timestamps()
    {
        $this->driver->put('test.txt', 'content');
        $time = time();
        $lastModified = $this->driver->lastModified('test.txt');
        
        $this->assertGreaterThanOrEqual($time - 1, $lastModified);
        $this->assertLessThanOrEqual($time + 1, $lastModified);
    }

    /** @test */
    public function it_maintains_content_integrity()
    {
        $content = str_repeat('test content', 100);
        $this->driver->put('test.txt', $content);
        
        $retrieved = $this->driver->get('test.txt');
        $this->assertEquals($content, $retrieved);
        $this->assertEquals(strlen($content), strlen($retrieved));
    }

    /** @test */
    public function it_handles_special_characters()
    {
        $content = "Special chars: àéîøü\n\t\r\n";
        $this->driver->put('test.txt', $content);
        $this->assertEquals($content, $this->driver->get('test.txt'));
    }

    /** @test */
    public function it_supports_partial_updates()
    {
        $this->driver->put('test.txt', 'initial');
        $this->driver->put('test.txt', 'updated');
        
        $this->assertEquals('updated', $this->driver->get('test.txt'));
    }

    /** @test */
    public function it_correctly_handles_exists_check()
    {
        $this->assertFalse($this->driver->exists('test.txt'));
        $this->driver->put('test.txt', 'content');
        $this->assertTrue($this->driver->exists('test.txt'));
        $this->driver->delete('test.txt');
        $this->assertFalse($this->driver->exists('test.txt'));
    }
}