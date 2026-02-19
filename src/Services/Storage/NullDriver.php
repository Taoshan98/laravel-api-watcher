<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Services\Storage;

use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;

class NullDriver implements ApiWatcherStorageDriver
{
    public function store(array $data): void
    {
        // Do nothing
    }

    public function get(array $filters = [], int $limit = 50, int $offset = 0): mixed
    {
        return [];
    }

    public function find(string $id): mixed
    {
        return null;
    }

    public function prune(int $days): int
    {
        return 0;
    }

    public function getStats(): array
    {
        return [
            'total_requests' => 0,
            'error_rate' => 0,
            'avg_latency' => 0,
            'active_users' => 0,
        ];
    }

    public function getRequestsPerDay(int $days = 7): array
    {
        return [];
    }

    public function getErrorRateTrend(int $days = 7): array
    {
        return [];
    }

    public function getStatusCodeDistribution(int $days = 7): array
    {
        return [];
    }

    public function getTopSlowestRoutes(int $days = 30, int $limit = 10): array
    {
        return [];
    }

    public function clear(): void
    {
        // Do nothing
    }
}
