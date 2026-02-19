<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Contracts;

interface ApiWatcherStorageDriver
{
    /**
     * Store the captured request data.
     *
     * @param array $data
     * @return void
     */
    public function store(array $data): void;

    /**
     * Retrieve requests based on filters.
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return mixed
     */
    public function get(array $filters = [], int $limit = 50, int $offset = 0): mixed;

    /**
     * Find a specific request by ID.
     *
     * @param string $id
     * @return mixed
     */
    public function find(string $id): mixed;

    /**
     * Delete requests older than the retention period.
     *
     * @param int $days
     * @return int Number of deleted records
     */
    public function prune(int $days): int;

    /**
     * Get aggregate statistics.
     *
     * @return array
     */
    public function getStats(): array;

    public function getRequestsPerDay(int $days = 30): array;

    public function getErrorRateTrend(int $days = 30): array;

    public function getStatusCodeDistribution(int $days = 30): array;

    public function getTopSlowestRoutes(int $days = 30, int $limit = 10): array;

    /**
     * Clear all recorded requests.
     *
     * @return void
     */
    public function clear(): void;
}
