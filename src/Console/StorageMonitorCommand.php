<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Rakshitbharat\LaravelStorageWithDatabase\DatabaseDriver;

class StorageMonitorCommand extends Command
{
    protected $signature = 'storage:monitor
                          {--reset : Reset the monitoring statistics}
                          {--cleanup : Clean up old monitoring statistics}
                          {--days= : Number of days to retain statistics when cleaning up}
                          {--operation= : Filter by specific operation}
                          {--summary : Show summary statistics only}';

    protected $description = 'Display storage operation statistics and performance metrics';

    public function handle()
    {
        $driver = Storage::disk('database')->getDriver();
        
        if (!method_exists($driver, 'getMonitor')) {
            $this->error('Monitoring is not available for this storage driver');
            return 1;
        }

        $monitor = $driver->getMonitor();
        
        if (!$monitor) {
            $this->error('Monitoring is not enabled. Enable it in config/storage-database.php');
            return 1;
        }

        if ($this->option('reset')) {
            $monitor->reset();
            $this->info('Monitoring statistics have been reset.');
            return 0;
        }

        if ($this->option('cleanup')) {
            $days = $this->option('days');
            $cleaned = $monitor->cleanup($days ? (int) $days : null);
            $this->info("Cleaned up {$cleaned} old statistic entries.");
            return 0;
        }

        if ($this->option('summary')) {
            $this->displaySummary($monitor->getSummary());
            return 0;
        }

        $operation = $this->option('operation');
        $stats = $operation ? $monitor->getStats($operation) : $monitor->getStats();

        if (empty($stats)) {
            $this->info('No statistics available.');
            return 0;
        }

        $this->displayStats($stats);
        return 0;
    }

    protected function displayStats(array $stats): void
    {
        $headers = ['Operation', 'Count', 'Avg Duration (ms)', 'Total Size', 'Last Operation'];
        $rows = [];

        foreach ($stats as $operation => $metrics) {
            $rows[] = [
                $operation,
                $metrics['count'] ?? 0,
                number_format($metrics['avg_duration'] ?? 0, 2),
                $this->formatBytes($metrics['total_size'] ?? 0),
                $metrics['last_operation'] ?? 'N/A',
            ];
        }

        $this->table($headers, $rows);

        // Display summary
        $totalOps = array_sum(array_column($stats, 'count'));
        $totalSize = array_sum(array_column($stats, 'total_size'));
        
        $this->line('');
        $this->info('Summary:');
        $this->line("Total Operations: {$totalOps}");
        $this->line("Total Storage Size: " . $this->formatBytes($totalSize));
    }

    protected function displaySummary(array $summary): void
    {
        $this->info('Storage Operations Summary:');
        $this->line('');
        $this->line("Total Operations: {$summary['total_operations']}");
        $this->line("Total Storage Size: " . $this->formatBytes($summary['total_size']));
        $this->line("Average Operation Duration: " . number_format($summary['avg_duration'], 2) . "ms");
        $this->line("First Operation: {$summary['first_operation']}");
        $this->line("Last Operation: {$summary['last_operation']}");
        
        $this->line('');
        $this->info('Operation Counts:');
        foreach ($summary['operation_counts'] as $operation => $count) {
            $this->line("  {$operation}: {$count}");
        }
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}