<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Console\Commands;

use Illuminate\Console\Command;
use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;

class GenerateSlaReport extends Command
{
    protected $signature = 'api-watcher:report {--days=30 : Days to analyze} {--path= : Report destination file path}';

    protected $description = 'Generate an SLA, Uptime %, and performance report for your API';

    public function handle(ApiWatcherStorageDriver $storage): int
    {
        $days = (int) $this->option('days');
        $path = (string) ($this->option('path') ?? storage_path("app/api-sla-report-{$days}d.json"));

        $this->info("Generating SLA report for the last {$days} days...");

        $stats = $storage->getStats();
        $total = (int) ($stats['total_requests'] ?? 0);
        $errorRate = (float) ($stats['error_rate'] ?? 0);
        $uptimePercentage = round(100.0 - $errorRate, 3);

        $report = [
            'generated_at' => now()->toIso8601String(),
            'period_days' => $days,
            'sla_metrics' => [
                'uptime_percentage' => $uptimePercentage,
                'error_rate_percentage' => $errorRate,
                'total_requests' => $total,
                'avg_latency_ms' => $stats['avg_latency'] ?? 0,
                'p95_latency_ms' => $stats['p95_latency'] ?? 0,
                'p99_latency_ms' => $stats['p99_latency'] ?? 0,
            ],
            'slowest_routes' => $storage->getTopSlowestRoutes($days, 5),
            'status_code_distribution' => $storage->getStatusCodeDistribution($days),
        ];

        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));

        $this->info("SLA Report successfully generated at {$path}");
        $this->table(['Metric', 'Value'], [
            ['Uptime Target / SLA', "{$uptimePercentage}%"],
            ['Error Rate', "{$errorRate}%"],
            ['Total Requests', $total],
            ['Average Latency', ($stats['avg_latency'] ?? 0).' ms'],
            ['P95 Latency', ($stats['p95_latency'] ?? 0).' ms'],
            ['P99 Latency', ($stats['p99_latency'] ?? 0).' ms'],
        ]);

        return Command::SUCCESS;
    }
}
