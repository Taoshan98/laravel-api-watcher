<?php

namespace Taoshan98\LaravelApiWatcher\Console\Commands;

use Illuminate\Console\Command;
use Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey;

class ListApiKeys extends Command
{
    protected $signature = 'api-watcher:list-keys';
    protected $description = 'List all API keys';

    public function handle()
    {
        $keys = ApiWatcherKey::all(['id', 'name', 'last_used_at', 'created_at']);

        if ($keys->isEmpty()) {
            $this->info('No API keys found.');
            return 0;
        }

        $this->table(
            ['ID', 'Name', 'Last Used', 'Created At'],
            $keys->toArray()
        );

        return 0;
    }
}
