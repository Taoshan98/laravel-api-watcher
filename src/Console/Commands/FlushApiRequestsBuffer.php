<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Console\Commands;

use Illuminate\Console\Command;
use Taoshan98\LaravelApiWatcher\Services\Storage\RedisDriver;

class FlushApiRequestsBuffer extends Command
{
    protected $signature = 'api-watcher:flush {--limit=500 : Maximum number of records to flush from Redis buffer}';

    protected $description = 'Flush buffered API requests from Redis into the database';

    public function handle(): int
    {
        $driverName = config('api-watcher.storage.driver');

        if ($driverName !== 'redis') {
            $this->warn("Storage driver is set to '{$driverName}'. Flushing is only required for 'redis' driver.");

            return 0;
        }

        $limit = (int) $this->option('limit');
        $driver = app(RedisDriver::class);

        $this->info("Flushing up to {$limit} buffered API requests to database...");
        $flushed = $driver->flush($limit);

        $this->info("Successfully flushed {$flushed} API request logs.");

        return 0;
    }
}
