<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Tests\Unit;

use Rakshitbharat\LaravelStorageWithDatabase\Tests\TestCase;
use Rakshitbharat\LaravelStorageWithDatabase\Monitoring\StorageMonitor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class StorageMonitorTest extends TestCase
{
    protected StorageMonitor $monitor;
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset cache before each test
        Cache::flush();

        Carbon::setTestNow(Carbon::now());

        $this->config = [
            'logging' => [
                'enabled' => true,
                'channel' => 'stack',
            ],
        ];

        $this->monitor = new StorageMonitor($this->config);
    }

    /** @test */
    public function it_records_operation_metrics()
    {
        Log::shouldReceive('channel')->once()->andReturnSelf();
        Log::shouldReceive('info')->once()->with(
            'Storage operation completed',
            \Mockery::on(function ($metric) {
                return $metric['operation'] === 'put' &&
                    $metric['path'] === 'test.txt' &&
                    isset($metric['duration']) &&
                    $metric['content_size'] === 100;
            })
        );

        $this->monitor->recordOperation('put', 'test.txt', 100);

        $stats = $this->monitor->getStats('put');
        $this->assertEquals(1, $stats['count']);
        $this->assertGreaterThan(0, $stats['total_duration']);
        $this->assertEquals(100, $stats['total_size']);
    }

    /** @test */
    public function it_aggregates_multiple_operations()
    {
        Log::shouldReceive('channel')->times(3)->andReturnSelf();
        Log::shouldReceive('info')->times(3);

        $this->monitor->recordOperation('get', 'test1.txt', 100);
        $this->monitor->recordOperation('get', 'test2.txt', 200);
        $this->monitor->recordOperation('get', 'test3.txt', 300);

        $stats = $this->monitor->getStats('get');
        $this->assertEquals(3, $stats['count']);
        $this->assertEquals(600, $stats['total_size']);
        $this->assertGreaterThan(0, $stats['avg_duration']);
    }

    /** @test */
    public function it_resets_statistics()
    {
        Log::shouldReceive('channel')->times(2)->andReturnSelf();
        Log::shouldReceive('info')->times(2);

        $this->monitor->recordOperation('put', 'test1.txt', 100);
        $this->monitor->recordOperation('get', 'test1.txt', 100);

        $this->assertNotEmpty($this->monitor->getStats());

        $this->monitor->reset();

        $this->assertEmpty($this->monitor->getStats());
        $this->assertEquals([
            'count' => 0,
            'total_duration' => 0,
            'avg_duration' => 0,
            'total_size' => 0,
        ], $this->monitor->getStats('put'));
    }

    /** @test */
    public function it_respects_disabled_monitoring()
    {
        $config = ['logging' => ['enabled' => false]];
        $monitor = new StorageMonitor($config);

        Log::shouldReceive('channel')->never();
        Log::shouldReceive('info')->never();

        $monitor->recordOperation('put', 'test.txt', 100);

        $this->assertEquals([
            'count' => 0,
            'total_duration' => 0,
            'avg_duration' => 0,
            'total_size' => 0,
        ], $monitor->getStats('put'));
    }

    /** @test */
    public function it_tracks_different_operation_types_separately()
    {
        Log::shouldReceive('channel')->times(3)->andReturnSelf();
        Log::shouldReceive('info')->times(3);

        $this->monitor->recordOperation('put', 'test.txt', 100);
        $this->monitor->recordOperation('get', 'test.txt', 100);
        $this->monitor->recordOperation('delete', 'test.txt');

        $stats = $this->monitor->getStats();
        
        $this->assertArrayHasKey('put', $stats);
        $this->assertArrayHasKey('get', $stats);
        $this->assertArrayHasKey('delete', $stats);
        
        $this->assertEquals(1, $stats['put']['count']);
        $this->assertEquals(1, $stats['get']['count']);
        $this->assertEquals(1, $stats['delete']['count']);
    }

    /** @test */
    public function it_cleans_up_old_statistics()
    {
        $config = [
            'logging' => ['enabled' => true],
            'monitoring' => ['stats_retention_days' => 30],
        ];

        $monitor = new StorageMonitor($config);

        // Create some old stats
        Cache::put('storage_monitor:stats:put', [
            'count' => 1,
            'first_seen' => now()->subDays(40)->toDateTimeString(),
        ]);

        Cache::put('storage_monitor:stats:get', [
            'count' => 1,
            'first_seen' => now()->subDays(20)->toDateTimeString(),
        ]);

        // Clean up stats older than 30 days
        $cleaned = $monitor->cleanup();

        $this->assertEquals(1, $cleaned);
        $this->assertNull(Cache::get('storage_monitor:stats:put'));
        $this->assertNotNull(Cache::get('storage_monitor:stats:get'));
    }

    /** @test */
    public function it_provides_summary_statistics()
    {
        $config = [
            'logging' => ['enabled' => true],
            'monitoring' => ['stats_retention_days' => 30],
        ];

        $monitor = new StorageMonitor($config);

        // Record some operations
        $monitor->recordOperation('put', 'test1.txt', 100);
        $monitor->recordOperation('put', 'test2.txt', 200);
        $monitor->recordOperation('get', 'test1.txt', 100);

        $summary = $monitor->getSummary();

        $this->assertEquals(3, $summary['total_operations']);
        $this->assertEquals(300, $summary['total_size']);
        $this->assertArrayHasKey('avg_duration', $summary);
        $this->assertArrayHasKey('operation_counts', $summary);
        $this->assertArrayHasKey('first_operation', $summary);
        $this->assertArrayHasKey('last_operation', $summary);
    }

    /** @test */
    public function it_handles_cleanup_with_invalid_dates()
    {
        $config = [
            'logging' => ['enabled' => true],
            'monitoring' => ['stats_retention_days' => 30],
        ];

        $monitor = new StorageMonitor($config);

        // Create stats with invalid date
        Cache::put('storage_monitor:stats:put', [
            'count' => 1,
            'first_seen' => 'invalid-date',
        ]);

        // Should not throw an exception
        $cleaned = $monitor->cleanup();
        $this->assertEquals(0, $cleaned);
    }

    /** @test */
    public function it_respects_custom_retention_period()
    {
        $config = [
            'logging' => ['enabled' => true],
            'monitoring' => ['stats_retention_days' => 7],
        ];

        $monitor = new StorageMonitor($config);

        Cache::put('storage_monitor:stats:put', [
            'count' => 1,
            'first_seen' => now()->subDays(10)->toDateTimeString(),
        ]);

        $cleaned = $monitor->cleanup();
        $this->assertEquals(1, $cleaned);
    }

    /** @test */
    public function it_maintains_accurate_average_durations()
    {
        $config = [
            'logging' => ['enabled' => true],
        ];

        $monitor = new StorageMonitor($config);

        // Simulate operations with different durations
        Carbon::setTestNow(now()->addMilliseconds(100));
        $monitor->recordOperation('put', 'test1.txt', 100);

        Carbon::setTestNow(now()->addMilliseconds(200));
        $monitor->recordOperation('put', 'test2.txt', 100);

        $stats = $monitor->getStats('put');
        $this->assertGreaterThan(0, $stats['avg_duration']);
        $this->assertEquals($stats['total_duration'] / $stats['count'], $stats['avg_duration']);
    }

    /** @test */
    public function it_handles_disabled_monitoring()
    {
        $config = [
            'logging' => ['enabled' => false],
        ];

        $monitor = new StorageMonitor($config);

        // Should not create any records
        $monitor->recordOperation('put', 'test.txt', 100);

        $stats = $monitor->getStats('put');
        $this->assertEquals(0, $stats['count']);
    }
}