<?php

namespace Taoshan98\LaravelApiWatcher\Console\Commands;

use Illuminate\Console\Command;
use Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey;

class RenameApiKey extends Command
{
    protected $signature = 'api-watcher:rename-key {id : The ID of the key} {name : The new name}';
    protected $description = 'Rename an API key';

    public function handle()
    {
        $id = $this->argument('id');
        $name = $this->argument('name');

        $key = ApiWatcherKey::find($id);

        if (!$key) {
            $this->error("Key with ID {$id} not found.");
            return 1;
        }

        $key->update(['name' => $name]);

        $this->info("Key renamed to '{$name}' successfully.");
        return 0;
    }
}
