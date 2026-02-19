<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Console\Commands;

use Illuminate\Console\Command;
use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;

class ClearApiRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-watcher:clear {--force : Force operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all recorded API requests';

    /**
     * Execute the console command.
     */
    public function handle(ApiWatcherStorageDriver $storage): int
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to clear ALL API requests? This action cannot be undone.')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $this->info("Clearing all requests...");
        
        $storage->clear();
        
        $this->info("All requests cleared.");

        return Command::SUCCESS;
    }
}
