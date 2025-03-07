<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Tests\Feature;

use Rakshitbharat\LaravelStorageWithDatabase\Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class StorageMonitorCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable monitoring for tests
        config(['storage-database.logging.enabled' => true]);
        
        // Reset cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_displays_storage_statistics()
    {
        $disk = Storage::disk('database');
        
        // Generate some test data
        $disk->put('test1.txt', 'Content 1');
        $disk->put('test2.txt', 'Content 2');
        $disk->get('test1.txt');
        $disk->delete('test2.txt');

        $this->artisan('storage:monitor')
            ->expectsOutput('Summary:')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_filter_by_operation()
    {
        $disk = Storage::disk('database');
        
        $disk->put('test1.txt', 'Content 1');
        $disk->put('test2.txt', 'Content 2');
        $disk->get('test1.txt');

        $this->artisan('storage:monitor', ['--operation' => 'put'])
            ->expectsTable(
                ['Operation', 'Count', 'Avg Duration (ms)', 'Total Size', 'Last Operation'],
                [['put', 2, number_format(0.00, 2), '20 B', now()->toDateTimeString()]]
            )
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_display_summary_statistics()
    {
        $disk = Storage::disk('database');
        
        $disk->put('test1.txt', 'Content 1');
        $disk->put('test2.txt', 'Content 2');
        $disk->get('test1.txt');

        $this->artisan('storage:monitor', ['--summary' => true])
            ->expectsOutput('Storage Operations Summary:')
            ->expectsOutput('Total Operations: 3')
            ->expectsOutput('Operation Counts:')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_cleanup_old_statistics()
    {
        $disk = Storage::disk('database');
        
        // Create some old stats
        Carbon::setTestNow(now()->subDays(40));
        $disk->put('old.txt', 'Old content');
        
        Carbon::setTestNow(now()->addDays(20)); // Now 20 days ago
        $disk->put('recent.txt', 'Recent content');
        
        Carbon::setTestNow(); // Back to present

        $this->artisan('storage:monitor', ['--cleanup' => true, '--days' => 30])
            ->expectsOutput('Cleaned up 1 old statistic entries.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_reset_statistics()
    {
        $disk = Storage::disk('database');
        
        $disk->put('test1.txt', 'Content 1');
        $disk->get('test1.txt');

        $this->artisan('storage:monitor', ['--reset' => true])
            ->expectsOutput('Monitoring statistics have been reset.')
            ->assertExitCode(0);

        $this->artisan('storage:monitor')
            ->expectsOutput('No statistics available.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_disabled_monitoring()
    {
        config(['storage-database.logging.enabled' => false]);

        $this->artisan('storage:monitor')
            ->expectsOutput('Monitoring is not enabled. Enable it in config/storage-database.php')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_displays_formatted_sizes()
    {
        $disk = Storage::disk('database');
        
        // Create a larger file to test size formatting
        $largeContent = str_repeat('x', 1024 * 1024); // 1MB
        $disk->put('large.txt', $largeContent);

        $this->artisan('storage:monitor')
            ->expectsOutput('Total Storage Size: 1.00 MB')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_empty_statistics()
    {
        $this->artisan('storage:monitor')
            ->expectsOutput('No statistics available.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_respects_custom_cleanup_period()
    {
        $disk = Storage::disk('database');
        
        Carbon::setTestNow(now()->subDays(15));
        $disk->put('old.txt', 'Old content');
        Carbon::setTestNow();

        $this->artisan('storage:monitor', ['--cleanup' => true, '--days' => 10])
            ->expectsOutput('Cleaned up 1 old statistic entries.')
            ->assertExitCode(0);
    }
}