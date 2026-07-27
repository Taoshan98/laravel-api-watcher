<?php

namespace Taoshan98\LaravelApiWatcher\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Taoshan98\LaravelApiWatcher\Http\Middleware\CaptureApiRequest;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;
use Taoshan98\LaravelApiWatcher\Support\CurlGenerator;
use Taoshan98\LaravelApiWatcher\Tests\TestCase;

class DevToolsAndQueryMetricsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->withoutMiddleware([\Illuminate\Auth\Middleware\Authenticate::class]);

        Config::set('api-watcher.enabled', true);
        Config::set('api-watcher.capture.async', false);

        Route::middleware(CaptureApiRequest::class)->get('api/query-test', function () {
            // Execute a DB query
            \Illuminate\Support\Facades\DB::select('select 1');

            return response()->json(['status' => 'ok']);
        });
    }

    #[Test]
    public function it_captures_database_query_metrics()
    {
        $this->get('api/query-test')->assertOk();

        $request = ApiRequest::first();
        expect($request)->not->toBeNull();
        expect($request->query_count)->toBe(1);
        expect($request->query_time_ms)->toBeGreaterThanOrEqual(0.0);
    }

    #[Test]
    public function it_generates_valid_curl_command()
    {
        $request = ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'POST',
            'url' => 'http://localhost/api/test',
            'status_code' => 200,
            'duration_ms' => 10,
            'request_headers' => ['Content-Type' => 'application/json', 'X-Custom' => 'HeaderVal'],
            'request_body' => json_encode(['foo' => 'bar']),
        ]);

        $curl = CurlGenerator::generate($request);

        expect($curl)->toContain('curl -X POST');
        expect($curl)->toContain('Content-Type: application/json');
        expect($curl)->toContain('{"foo":"bar"}');
        expect($curl)->toContain('http://localhost/api/test');
    }

    #[Test]
    public function endpoint_returns_curl_command()
    {
        $request = ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'GET',
            'url' => 'http://localhost/api/test',
            'status_code' => 200,
            'duration_ms' => 10,
        ]);

        $response = $this->getJson("/api-watcher/api/requests/{$request->id}/curl");
        $response->assertOk()
            ->assertJsonPath('id', $request->id)
            ->assertJsonStructure(['curl']);
    }

    #[Test]
    public function endpoint_generates_signed_shareable_link()
    {
        $request = ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'GET',
            'url' => 'http://localhost/api/test',
            'status_code' => 200,
            'duration_ms' => 10,
        ]);

        $response = $this->postJson("/api-watcher/api/requests/{$request->id}/share");
        $response->assertOk()
            ->assertJsonStructure(['share_url', 'expires_at']);

        $shareUrl = $response->json('share_url');

        // Verify signed link can be accessed
        $this->getJson($shareUrl)->assertOk()->assertJsonPath('id', $request->id);
    }
}
