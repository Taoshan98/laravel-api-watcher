<?php

namespace Taoshan98\LaravelApiWatcher\Tests\Feature;

use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Taoshan98\LaravelApiWatcher\Listeners\CaptureOutgoingHttpRequest;
use Taoshan98\LaravelApiWatcher\Models\ApiOutgoingRequest;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;
use Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey;
use Taoshan98\LaravelApiWatcher\Services\Storage\NullDriver;
use Taoshan98\LaravelApiWatcher\Services\Storage\RedisDriver;
use Taoshan98\LaravelApiWatcher\Tests\TestCase;

class FullCoverageControllersAndStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->withoutMiddleware([\Illuminate\Auth\Middleware\Authenticate::class]);
        Config::set('api-watcher.enabled', true);
    }

    #[Test]
    public function null_driver_handles_all_methods_safely()
    {
        $nullDriver = new NullDriver;
        $nullDriver->store(['id' => '123']);
        $nullDriver->storeBatch([['id' => '123']]);
        expect($nullDriver->get())->toBe([]);
        expect($nullDriver->find('123'))->toBeNull();
        expect($nullDriver->prune(30))->toBe(0);
        expect($nullDriver->getStats()['total_requests'])->toBe(0);
        expect($nullDriver->getRequestsPerDay())->toBe([]);
        expect($nullDriver->getErrorRateTrend())->toBe([]);
        expect($nullDriver->getStatusCodeDistribution())->toBe([]);
        expect($nullDriver->getTopSlowestRoutes())->toBe([]);
        $nullDriver->clear();
    }

    #[Test]
    public function redis_driver_delegates_analytics_to_fallback()
    {
        $redisDriver = new RedisDriver;
        expect($redisDriver->get())->not->toBeNull();
        expect($redisDriver->find('non-existent'))->toBeNull();
        expect($redisDriver->prune(30))->toBeInt();
        expect($redisDriver->getStats())->toBeArray();
        expect($redisDriver->getRequestsPerDay())->toBeArray();
        expect($redisDriver->getErrorRateTrend())->toBeArray();
        expect($redisDriver->getStatusCodeDistribution())->toBeArray();
        expect($redisDriver->getTopSlowestRoutes())->toBeArray();
    }

    #[Test]
    public function capture_outgoing_http_request_handles_connection_failed()
    {
        $listener = app(CaptureOutgoingHttpRequest::class);

        $httpRequest = new HttpClientRequest(new \GuzzleHttp\Psr7\Request('GET', 'https://failed-domain.test/api'));
        $event = new ConnectionFailed($httpRequest, new \Illuminate\Http\Client\ConnectionException('Connection Failed'));

        $listener->handleConnectionFailed($event);

        $outgoing = ApiOutgoingRequest::where('domain', 'failed-domain.test')->first();
        expect($outgoing)->not->toBeNull();
        expect($outgoing->status_code)->toBe(0);
        expect($outgoing->exception_info)->toBe('Connection Failed');
    }

    #[Test]
    public function api_watcher_controller_actions_work_properly()
    {
        Config::set('api-watcher.alerts.enabled', true);
        Config::set('api-watcher.alerts.notifications.mail.to', 'admin@example.com');

        $req = ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'GET',
            'url' => 'http://localhost/api/test-route',
            'status_code' => 200,
            'duration_ms' => 10,
        ]);

        $this->getJson('/api-watcher/api/analytics')->assertOk();
        $this->getJson('/api-watcher/api/config')->assertOk();
        $this->postJson('/api-watcher/api/actions/test-alert')->assertOk();

        // Replay endpoint
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['status' => 'ok'], 200)]);
        $this->postJson("/api-watcher/api/requests/{$req->id}/replay")->assertOk();

        // Key management endpoints
        $keyToken = ApiWatcherKey::createKey('Test Key', ['read:stats']);
        $key = ApiWatcherKey::latest()->first();

        $this->patchJson("/api-watcher/api/keys/{$key->id}", ['name' => 'Updated Key'])->assertOk();
        $this->postJson("/api-watcher/api/keys/{$key->id}/refresh")->assertOk();
        $this->deleteJson("/api-watcher/api/keys/{$key->id}")->assertOk();

        // Prune and clear actions
        $this->postJson('/api-watcher/api/actions/prune', ['days' => 30])->assertOk();
        $this->postJson('/api-watcher/api/actions/clear')->assertOk();
    }
}
