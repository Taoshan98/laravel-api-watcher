<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Console\Commands;

use Illuminate\Console\Command;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;

class ExportPostmanCollection extends Command
{
    protected $signature = 'api-watcher:export-postman {--path= : Output JSON file path}';

    protected $description = 'Export captured API requests as a Postman Collection v2.1';

    public function handle(): int
    {
        $path = (string) ($this->option('path') ?? storage_path('app/api-watcher-postman-collection.json'));

        $this->info('Generating Postman Collection v2.1...');

        $requests = ApiRequest::latest('created_at')->limit(1000)->get();

        $items = [];
        foreach ($requests as $req) {
            /** @var ApiRequest $req */
            $urlParts = parse_url($req->url);
            $host = explode('.', (string) ($urlParts['host'] ?? 'localhost'));
            $pathParts = array_values(array_filter(explode('/', (string) ($urlParts['path'] ?? ''))));

            $headers = [];
            if (is_array($req->request_headers)) {
                foreach ($req->request_headers as $k => $v) {
                    $headers[] = [
                        'key' => $k,
                        'value' => is_array($v) ? implode(', ', $v) : (string) $v,
                    ];
                }
            }

            $items[] = [
                'name' => "{$req->method} ".($urlParts['path'] ?? '/'),
                'request' => [
                    'method' => $req->method,
                    'header' => $headers,
                    'body' => $req->request_body ? [
                        'mode' => 'raw',
                        'raw' => $req->request_body,
                    ] : null,
                    'url' => [
                        'raw' => $req->url,
                        'host' => $host,
                        'path' => $pathParts,
                    ],
                ],
            ];
        }

        $collection = [
            'info' => [
                'name' => 'Laravel API Watcher Collection',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => $items,
        ];

        file_put_contents($path, json_encode($collection, JSON_PRETTY_PRINT));

        $this->info("Successfully exported Postman Collection to {$path}");

        return Command::SUCCESS;
    }
}
