<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Console\Commands;

use Illuminate\Console\Command;
use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;

class ExportApiRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-watcher:export {--format=json : Format to export (json|csv)} {--path= : Export file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export API requests to a file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $format = $this->option('format');
        $path = $this->option('path') ?? storage_path("app/api-requests-export-".date('Y-m-d-His').".{$format}");

        if (!in_array($format, ['json', 'csv'])) {
            $this->error('Invalid format. Supported formats: json, csv');
            return Command::FAILURE;
        }

        $this->info("Exporting requests to {$path}...");

        $query = ApiRequest::query();

        if ($format === 'json') {
            $data = $query->get();
            file_put_contents($path, $data->toJson(JSON_PRETTY_PRINT));
        } else {
            $file = fopen($path, 'w');
            $headers = ['id', 'method', 'url', 'status_code', 'ip_address', 'duration_ms', 'created_at'];
            fputcsv($file, $headers);

            $query->chunk(500, function ($requests) use ($file) {
                foreach ($requests as $request) {
                    fputcsv($file, [
                        $request->id,
                        $request->method,
                        $request->url,
                        $request->status_code,
                        $request->ip_address,
                        $request->duration_ms,
                        $request->created_at,
                    ]);
                }
            });

            fclose($file);
        }

        $this->info("Export completed!");

        return Command::SUCCESS;
    }
}
