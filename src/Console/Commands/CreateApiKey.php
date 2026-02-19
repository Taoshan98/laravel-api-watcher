<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Console\Commands;

use Illuminate\Console\Command;
use Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey;

class CreateApiKey extends Command
{
    protected $signature = 'api-watcher:create-key {name : The name of the API key}';

    protected $description = 'Create a new API Key for the external API';

    public function handle(): int
    {
        $name = $this->argument('name');
        
        $plainTextToken = ApiWatcherKey::createKey($name);

        $this->info("API Key created successfully for: {$name}");
        $this->line("Key: <comment>{$plainTextToken}</comment>");
        $this->warn('Copy this key now. You will not be able to see it again.');

        return 0;
    }
}
