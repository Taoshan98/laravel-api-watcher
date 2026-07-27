<?php

namespace Taoshan98\LaravelApiWatcher\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;
use Taoshan98\LaravelApiWatcher\Services\Storage\RedisDriver;
use Taoshan98\LaravelApiWatcher\Tests\TestCase;

class SamplingAndRedisDriverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    #[Test]
    public function it_skips_sampling_when_sampling_rate_is_zero_for_200_ok()
    {
        Config::set('api-watcher.enabled', true);
        Config::set('api-watcher.sampling.enabled', true);
        Config::set('api-watcher.sampling.rate', 0.0);
        Config::set('api-watcher.sampling.always_sample_errors', true);

        $this->getJson('/api-watcher/api/v1/stats'); // 403 or whatever, but let's test a 200 route

        // Let's create a 200 OK request test
        $service = app(\Taoshan98\LaravelApiWatcher\Services\RequestCaptureService::class);
        $request = \Illuminate\Http\Request::create('/api/sample-test', 'GET');
        $response = new \Illuminate\Http\Response('ok', 200);

        $service->capture($request, $response, microtime(true));

        expect(ApiRequest::count())->toBe(0);
    }

    #[Test]
    public function it_always_samples_errors_even_if_sampling_rate_is_zero()
    {
        Config::set('api-watcher.enabled', true);
        Config::set('api-watcher.sampling.enabled', true);
        Config::set('api-watcher.sampling.rate', 0.0);
        Config::set('api-watcher.sampling.always_sample_errors', true);
        Config::set('api-watcher.capture.async', false);

        $service = app(\Taoshan98\LaravelApiWatcher\Services\RequestCaptureService::class);
        $request = \Illuminate\Http\Request::create('/api/sample-error', 'GET');
        $response = new \Illuminate\Http\Response('error', 500);

        $service->capture($request, $response, microtime(true));

        expect(ApiRequest::count())->toBe(1);
    }

    #[Test]
    public function redis_driver_flushes_items_to_database()
    {
        $redisMock = \Mockery::mock();
        $redisMock->shouldReceive('rpush')->atLeast()->once();
        $redisMock->shouldReceive('lpop')
            ->once()
            ->andReturn(json_encode([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'method' => 'GET',
                'url' => 'http://localhost/api/buffered',
                'status_code' => 200,
                'duration_ms' => 15,
                'created_at' => now()->toDateTimeString(),
            ]));
        $redisMock->shouldReceive('lpop')->once()->andReturn(null);

        Redis::shouldReceive('connection')->andReturn($redisMock);

        $driver = new RedisDriver;
        $driver->store(['method' => 'GET', 'url' => 'http://localhost/api/buffered']);

        $flushed = $driver->flush();
        expect($flushed)->toBe(1);
        expect(ApiRequest::count())->toBe(1);
    }
}
