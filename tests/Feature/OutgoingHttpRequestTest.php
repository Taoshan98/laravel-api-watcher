<?php

namespace Taoshan98\LaravelApiWatcher\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Taoshan98\LaravelApiWatcher\Models\ApiOutgoingRequest;
use Taoshan98\LaravelApiWatcher\Tests\TestCase;

class OutgoingHttpRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->withoutMiddleware([\Illuminate\Auth\Middleware\Authenticate::class]);
        Config::set('api-watcher.enabled', true);
    }

    #[Test]
    public function it_captures_outgoing_http_client_requests()
    {
        Http::fake([
            'api.stripe.com/*' => Http::response(['id' => 'ch_123', 'status' => 'succeeded'], 200),
        ]);

        Http::post('https://api.stripe.com/v1/charges', ['amount' => 1000]);

        expect(ApiOutgoingRequest::count())->toBe(1);

        $outgoing = ApiOutgoingRequest::first();
        expect($outgoing->domain)->toBe('api.stripe.com');
        expect($outgoing->method)->toBe('POST');
        expect($outgoing->status_code)->toBe(200);
        expect($outgoing->request_body)->toContain('amount');
        expect($outgoing->response_body)->toContain('ch_123');
    }

    #[Test]
    public function endpoint_returns_outgoing_requests_list_and_stats()
    {
        ApiOutgoingRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'domain' => 'api.openai.com',
            'method' => 'POST',
            'url' => 'https://api.openai.com/v1/chat/completions',
            'status_code' => 200,
            'duration_ms' => 350,
        ]);

        $response = $this->getJson('/api-watcher/api/outgoing-requests');
        $response->assertOk()->assertJsonCount(1);

        $statsResponse = $this->getJson('/api-watcher/api/outgoing-requests/stats');
        $statsResponse->assertOk()
            ->assertJsonPath('total_outgoing_requests', 1)
            ->assertJsonPath('avg_latency', 350);
    }
}
