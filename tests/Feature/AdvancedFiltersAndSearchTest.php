<?php

namespace Taoshan98\LaravelApiWatcher\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;
use Taoshan98\LaravelApiWatcher\Tests\TestCase;

class AdvancedFiltersAndSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->withoutMiddleware([\Illuminate\Auth\Middleware\Authenticate::class]);
        Config::set('api-watcher.enabled', true);
    }

    #[Test]
    public function global_search_matches_url_and_method_and_body()
    {
        ApiRequest::create([
            'id' => (string) Str::uuid(),
            'method' => 'POST',
            'url' => 'https://example.com/api/v1/orders/checkout',
            'status_code' => 201,
            'duration_ms' => 85,
            'request_body' => json_encode(['customer_email' => 'mario.rossi@example.com']),
            'response_body' => json_encode(['order_id' => 'ORD-999']),
        ]);

        ApiRequest::create([
            'id' => (string) Str::uuid(),
            'method' => 'GET',
            'url' => 'https://example.com/api/v1/products',
            'status_code' => 200,
            'duration_ms' => 25,
            'request_body' => null,
            'response_body' => json_encode(['items' => []]),
        ]);

        $storage = app(ApiWatcherStorageDriver::class);

        // Search by URL substring
        $resultsUrl = $storage->get(['search' => 'orders']);
        expect($resultsUrl)->toHaveCount(1);
        expect($resultsUrl->first()->url)->toContain('orders/checkout');

        // Search by body content
        $resultsEmail = $storage->get(['search' => 'mario.rossi']);
        expect($resultsEmail)->toHaveCount(1);

        // Search by status code numeric
        $resultsStatus = $storage->get(['search' => '201']);
        expect($resultsStatus)->toHaveCount(1);

        // Count with search
        $count = $storage->count(['search' => 'orders']);
        expect($count)->toBe(1);
    }

    #[Test]
    public function status_code_range_filters_correctly()
    {
        ApiRequest::create([
            'id' => (string) Str::uuid(),
            'method' => 'GET',
            'url' => 'https://example.com/api/success',
            'status_code' => 200,
            'duration_ms' => 10,
        ]);

        ApiRequest::create([
            'id' => (string) Str::uuid(),
            'method' => 'POST',
            'url' => 'https://example.com/api/not-found',
            'status_code' => 404,
            'duration_ms' => 15,
        ]);

        ApiRequest::create([
            'id' => (string) Str::uuid(),
            'method' => 'POST',
            'url' => 'https://example.com/api/server-error',
            'status_code' => 500,
            'duration_ms' => 200,
        ]);

        $storage = app(ApiWatcherStorageDriver::class);

        // Filter 2xx
        $results2xx = $storage->get(['status_code' => ['2xx']]);
        expect($results2xx)->toHaveCount(1);
        expect($results2xx->first()->status_code)->toBe(200);

        // Filter 4xx and 5xx together
        $resultsErrors = $storage->get(['status_code' => ['4xx', '5xx']]);
        expect($resultsErrors)->toHaveCount(2);

        // Count errors
        expect($storage->count(['status_code' => ['4xx', '5xx']]))->toBe(2);
    }

    #[Test]
    public function duration_and_date_range_filters_work_properly()
    {
        ApiRequest::create([
            'id' => (string) Str::uuid(),
            'method' => 'GET',
            'url' => 'https://example.com/api/fast',
            'status_code' => 200,
            'duration_ms' => 50,
            'created_at' => now()->subHours(2),
        ]);

        ApiRequest::create([
            'id' => (string) Str::uuid(),
            'method' => 'GET',
            'url' => 'https://example.com/api/slow',
            'status_code' => 200,
            'duration_ms' => 800,
            'created_at' => now()->subMinutes(10),
        ]);

        $storage = app(ApiWatcherStorageDriver::class);

        // Duration >= 100ms
        $slowOnly = $storage->get(['duration_min' => 100]);
        expect($slowOnly)->toHaveCount(1);
        expect($slowOnly->first()->duration_ms)->toBe(800);

        // Duration <= 100ms
        $fastOnly = $storage->get(['duration_max' => 100]);
        expect($fastOnly)->toHaveCount(1);
        expect($fastOnly->first()->duration_ms)->toBe(50);

        // Date from last 30 minutes
        $recent = $storage->get(['date_from' => now()->subMinutes(30)->toDateTimeString()]);
        expect($recent)->toHaveCount(1);
        expect($recent->first()->url)->toContain('slow');
    }

    #[Test]
    public function api_watcher_controller_returns_paginated_response()
    {
        for ($i = 1; $i <= 15; $i++) {
            ApiRequest::create([
                'id' => (string) Str::uuid(),
                'method' => 'GET',
                'url' => "https://example.com/api/item/{$i}",
                'status_code' => 200,
                'duration_ms' => $i * 10,
            ]);
        }

        $response = $this->getJson('/api-watcher/api/requests?per_page=5&page=2');
        $response->assertOk()
            ->assertJsonPath('total', 15)
            ->assertJsonPath('page', 2)
            ->assertJsonPath('per_page', 5)
            ->assertJsonPath('last_page', 3)
            ->assertJsonCount(5, 'data');
    }
}
