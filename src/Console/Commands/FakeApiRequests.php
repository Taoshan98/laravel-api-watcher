<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Console\Commands;

use Illuminate\Console\Command;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;
use Taoshan98\LaravelApiWatcher\Database\Factories\ApiRequestFactory;

class FakeApiRequests extends Command
{
    protected $signature = 'api-watcher:fake {count=100 : The number of fake requests to generate}';

    protected $description = 'Generate fake API requests for testing the dashboard';

    public function handle(): int
    {
        $count = (int) $this->argument('count');

        $this->info("Generating {$count} fake API requests...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            ApiRequestFactory::new()->create([
                'created_at' => now()->subMinutes(rand(0, 10080)), // Last 7 days
            ]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Success! Generated {$count} fake requests.");

        return 0;
    }
}
