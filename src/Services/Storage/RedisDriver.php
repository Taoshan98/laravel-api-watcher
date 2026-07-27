<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Services\Storage;

use Illuminate\Support\Facades\Redis;
use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;

class RedisDriver implements ApiWatcherStorageDriver
{
    protected DatabaseDriver $fallback;

    protected string $connection;

    protected string $key;

    public function __construct(?DatabaseDriver $fallback = null)
    {
        $this->fallback = $fallback ?? new DatabaseDriver;
        $this->connection = (string) config('api-watcher.storage.redis.connection', 'default');
        $this->key = (string) config('api-watcher.storage.redis.key', 'api_watcher:buffer');
    }

    public function store(array $data): void
    {
        /** @var mixed $redis */
        $redis = Redis::connection($this->connection);
        $redis->rpush($this->key, json_encode($data));
    }

    public function storeBatch(array $batch): void
    {
        if (empty($batch)) {
            return;
        }

        /** @var mixed $redis */
        $redis = Redis::connection($this->connection);
        foreach ($batch as $data) {
            $redis->rpush($this->key, json_encode($data));
        }
    }

    /**
     * Flush buffered requests from Redis into the Database.
     *
     * @return int Count of flushed records
     */
    public function flush(int $limit = 500): int
    {
        /** @var mixed $redis */
        $redis = Redis::connection($this->connection);
        $batch = [];
        $flushed = 0;

        while ($flushed < $limit) {
            $item = $redis->lpop($this->key);
            if (! $item) {
                break;
            }

            $decoded = json_decode($item, true);
            if (is_array($decoded)) {
                $batch[] = $decoded;
                $flushed++;
            }
        }

        if (! empty($batch)) {
            $this->fallback->storeBatch($batch);
        }

        return $flushed;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function get(array $filters = [], int $limit = 50, int $offset = 0): mixed
    {
        return $this->fallback->get($filters, $limit, $offset);
    }

    public function find(string $id): mixed
    {
        return $this->fallback->find($id);
    }

    public function prune(int $days): int
    {
        return $this->fallback->prune($days);
    }

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return $this->fallback->getStats();
    }

    /**
     * @return array<string, int>
     */
    public function getRequestsPerDay(int $days = 30): array
    {
        return $this->fallback->getRequestsPerDay($days);
    }

    /**
     * @return array<string, float>
     */
    public function getErrorRateTrend(int $days = 30): array
    {
        return $this->fallback->getErrorRateTrend($days);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getStatusCodeDistribution(int $days = 30): array
    {
        return $this->fallback->getStatusCodeDistribution($days);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopSlowestRoutes(int $days = 30, int $limit = 10): array
    {
        return $this->fallback->getTopSlowestRoutes($days, $limit);
    }

    public function clear(): void
    {
        /** @var mixed $redis */
        $redis = Redis::connection($this->connection);
        $redis->del($this->key);
        $this->fallback->clear();
    }
}
