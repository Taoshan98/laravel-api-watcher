<?php

namespace Taoshan98\LaravelApiWatcher\Console\Commands;

use Illuminate\Console\Command;
use Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey;

class RegenerateApiKey extends Command
{
    protected $signature = 'api-watcher:regenerate-key {id : The ID of the key} {--force : Force operation without confirmation}';
    protected $description = 'Regenerate an API key token';

    public function handle()
    {
        $id = $this->argument('id');
        $key = ApiWatcherKey::find($id);

        if (!$key) {
            $this->error("Key with ID {$id} not found.");
            return 1;
        }

        if (!$this->option('force') && !$this->confirm("Are you sure you want to regenerate the key for '{$key->name}'? The old key will stop working immediately.")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $token = $key->regenerate();

        $this->info('Key regenerated successfully.');
        $this->info("New Token: {$token}");
        $this->warn('Copy this token now. You will not be able to see it again.');

        return 0;
    }
}
