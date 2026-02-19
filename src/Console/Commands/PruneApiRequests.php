<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Console\Commands;

use Illuminate\Console\Command;
use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;

class PruneApiRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-watcher:prune {--days=30 : Number of days to retain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune API requests older than a specified number of days';

    /**
     * Execute the console command.
     */
    public function handle(ApiWatcherStorageDriver $storage): int
    {
        $days = (int) $this->option('days');
        
        $this->info("Pruning requests older than {$days} days...");
        
        $count = $storage->prune($days);
        
        $this->info("Deleted {$count} requests.");

        return Command::SUCCESS;
    }
}
