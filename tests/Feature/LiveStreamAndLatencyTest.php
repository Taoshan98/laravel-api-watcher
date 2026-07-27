<?php

namespace Taoshan98\LaravelApiWatcher\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;
use Taoshan98\LaravelApiWatcher\Services\Storage\DatabaseDriver;
use Taoshan98\LaravelApiWatcher\Tests\TestCase;

class LiveStreamAndLatencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->withoutMiddleware([\Illuminate\Auth\Middleware\Authenticate::class]);
    }

    #[Test]
    public function it_calculates_p95_and_p99_latency()
    {
        for ($i = 1; $i <= 100; $i++) {
            ApiRequest::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'method' => 'GET',
                'url' => 'http://localhost/api/test',
                'status_code' => 200,
                'duration_ms' => $i * 10,
            ]);
        }

        $driver = new DatabaseDriver;
        $stats = $driver->getStats();

        expect($stats['p95_latency'])->toBe(960);
        expect($stats['p99_latency'])->toBe(1000);
    }

    #[Test]
    public function sse_live_stream_endpoint_returns_event_stream()
    {
        $response = $this->get('/api-watcher/api/live-stream');

        expect($response->isOk())->toBeTrue();
        expect($response->headers->get('Content-Type'))->toContain('text/event-stream');
    }
}
