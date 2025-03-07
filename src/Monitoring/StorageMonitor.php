<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Monitoring;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class StorageMonitor
{
    protected string $logChannel;
    protected bool $enabled;
    protected array $metrics = [];
    protected float $startTime;
    protected int $retentionDays;

    public function __construct(array $config)
    {
        $this->logChannel = $config['logging']['channel'] ?? 'stack';
        $this->enabled = $config['logging']['enabled'] ?? false;
        $this->startTime = microtime(true);
        $this->retentionDays = $config['monitoring']['stats_retention_days'] ?? 30;
    }

    public function recordOperation(string $operation, string $path, ?int $contentSize = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $duration = microtime(true) - $this->startTime;
        $metric = [
            'operation' => $operation,
            'path' => $path,
            'duration' => round($duration * 1000, 2), // Convert to milliseconds
            'timestamp' => now()->toDateTimeString(),
            'content_size' => $contentSize,
        ];

        $this->metrics[] = $metric;
        $this->logMetric($metric);
        $this->updateAggregates($metric);
    }

    protected function logMetric(array $metric): void
    {
        Log::channel($this->logChannel)->info('Storage operation completed', $metric);
    }

    protected function updateAggregates(array $metric): void
    {
        $cacheKey = "storage_monitor:stats:{$metric['operation']}";
        
        $stats = Cache::get($cacheKey, [
            'count' => 0,
            'total_duration' => 0,
            'avg_duration' => 0,
            'total_size' => 0,
            'last_operation' => null,
            'first_seen' => now()->toDateTimeString(),
        ]);

        $stats['count']++;
        $stats['total_duration'] += $metric['duration'];
        $stats['avg_duration'] = $stats['total_duration'] / $stats['count'];
        $stats['last_operation'] = $metric['timestamp'];
        
        if ($metric['content_size']) {
            $stats['total_size'] += $metric['content_size'];
        }

        Cache::put($cacheKey, $stats, now()->addDays($this->retentionDays));
    }

    public function getStats(?string $operation = null): array
    {
        if ($operation) {
            return Cache::get("storage_monitor:stats:{$operation}", [
                'count' => 0,
                'total_duration' => 0,
                'avg_duration' => 0,
                'total_size' => 0,
                'last_operation' => null,
                'first_seen' => now()->toDateTimeString(),
            ]);
        }

        $allStats = [];
        $operations = ['put', 'get', 'delete', 'copy', 'move', 'files', 'allFiles'];
        
        foreach ($operations as $op) {
            $stats = Cache::get("storage_monitor:stats:{$op}");
            if ($stats) {
                $allStats[$op] = $stats;
            }
        }

        return $allStats;
    }

    public function reset(): void
    {
        $this->metrics = [];
        $operations = ['put', 'get', 'delete', 'copy', 'move', 'files', 'allFiles'];
        
        foreach ($operations as $op) {
            Cache::forget("storage_monitor:stats:{$op}");
        }
    }

    public function cleanup(?int $days = null): int
    {
        $days = $days ?? $this->retentionDays;
        $cutoff = now()->subDays($days);
        $cleaned = 0;

        $operations = ['put', 'get', 'delete', 'copy', 'move', 'files', 'allFiles'];
        foreach ($operations as $op) {
            $cacheKey = "storage_monitor:stats:{$op}";
            $stats = Cache::get($cacheKey);
            
            if ($stats && isset($stats['first_seen'])) {
                $firstSeen = Carbon::parse($stats['first_seen']);
                if ($firstSeen->lt($cutoff)) {
                    Cache::forget($cacheKey);
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    public function getSummary(): array
    {
        $stats = $this->getStats();
        
        return [
            'total_operations' => array_sum(array_column($stats, 'count')),
            'total_size' => array_sum(array_column($stats, 'total_size')),
            'avg_duration' => array_sum(array_column($stats, 'avg_duration')) / count($stats),
            'operation_counts' => array_column($stats, 'count', 'operation'),
            'first_operation' => min(array_column($stats, 'first_seen')),
            'last_operation' => max(array_column($stats, 'last_operation')),
        ];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}