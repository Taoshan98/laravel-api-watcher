<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Services\Storage;

use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;

class NullDriver implements ApiWatcherStorageDriver
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): void
    {
        // Do nothing
    }

    /**
     * @param  array<int, array<string, mixed>>  $batch
     */
    public function storeBatch(array $batch): void
    {
        // Do nothing
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function get(array $filters = [], int $limit = 50, int $offset = 0): mixed
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function count(array $filters = []): int
    {
        return 0;
    }

    public function find(string $id): mixed
    {
        return null;
    }

    public function prune(int $days): int
    {
        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return [
            'total_requests' => 0,
            'error_rate' => 0,
            'avg_latency' => 0,
            'p95_latency' => 0,
            'p99_latency' => 0,
            'active_users' => 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getRequestsPerDay(int $days = 7): array
    {
        return [];
    }

    /**
     * @return array<string, float>
     */
    public function getErrorRateTrend(int $days = 7): array
    {
        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getStatusCodeDistribution(int $days = 7): array
    {
        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopSlowestRoutes(int $days = 30, int $limit = 10): array
    {
        return [];
    }

    public function clear(): void
    {
        // Do nothing
    }
}
