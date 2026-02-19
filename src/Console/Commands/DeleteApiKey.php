<?php

namespace Taoshan98\LaravelApiWatcher\Console\Commands;

use Illuminate\Console\Command;
use Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey;

class DeleteApiKey extends Command
{
    protected $signature = 'api-watcher:delete-key {id : The ID of the key} {--force : Force operation without confirmation}';
    protected $description = 'Delete an API key';

    public function handle()
    {
        $id = $this->argument('id');
        $key = ApiWatcherKey::find($id);

        if (!$key) {
            $this->error("Key with ID {$id} not found.");
            return 1;
        }

        if (!$this->option('force') && !$this->confirm("Are you sure you want to delete the key '{$key->name}'? This action cannot be undone.")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $key->delete();

        $this->info('Key deleted successfully.');
        return 0;
    }
}
