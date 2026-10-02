<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Contracts;

interface ApiWatcherStorageDriver
{
    /**
     * Store the captured request data.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): void;

    /**
     * Store a batch of captured request data.
     *
     * @param  array<int, array<string, mixed>>  $batch
     */
    public function storeBatch(array $batch): void;

    /**
     * Retrieve requests based on filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function get(array $filters = [], int $limit = 50, int $offset = 0): mixed;

    /**
     * Count requests based on filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function count(array $filters = []): int;

    /**
     * Find a specific request by ID.
     */
    public function find(string $id): mixed;

    /**
     * Delete requests older than the retention period.
     *
     * @return int Number of deleted records
     */
    public function prune(int $days): int;

    /**
     * Get aggregate statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array;

    /**
     * @return array<string, int>
     */
    public function getRequestsPerDay(int $days = 30): array;

    /**
     * @return array<string, float>
     */
    public function getErrorRateTrend(int $days = 30): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getStatusCodeDistribution(int $days = 30): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopSlowestRoutes(int $days = 30, int $limit = 10): array;

    /**
     * Clear all recorded requests.
     */
    public function clear(): void;
}
