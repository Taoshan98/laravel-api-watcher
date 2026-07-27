<?php

namespace Taoshan98\LaravelApiWatcher\Tests\Feature;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;
use Taoshan98\LaravelApiWatcher\Support\AssetManager;
use Taoshan98\LaravelApiWatcher\Tests\TestCase;

class FullCoverageCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->withoutMiddleware([\Illuminate\Auth\Middleware\Authenticate::class]);
        Config::set('api-watcher.enabled', true);
    }

    #[Test]
    public function command_clear_removes_all_requests()
    {
        ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'GET',
            'url' => 'http://localhost/test',
            'status_code' => 200,
            'duration_ms' => 10,
        ]);

        expect(ApiRequest::count())->toBe(1);

        $this->artisan('api-watcher:clear', ['--force' => true])->assertExitCode(0);

        expect(ApiRequest::count())->toBe(0);
    }

    #[Test]
    public function command_prune_removes_old_requests()
    {
        ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'GET',
            'url' => 'http://localhost/old',
            'status_code' => 200,
            'duration_ms' => 10,
            'created_at' => now()->subDays(40),
        ]);

        $this->artisan('api-watcher:prune', ['--days' => 30])->assertExitCode(0);

        expect(ApiRequest::count())->toBe(0);
    }

    #[Test]
    public function command_fake_generates_mock_requests()
    {
        $this->artisan('api-watcher:fake', ['count' => 5])->assertExitCode(0);

        expect(ApiRequest::count())->toBe(5);
    }

    #[Test]
    public function command_export_csv_and_invalid_format()
    {
        ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'GET',
            'url' => 'http://localhost/csv-test',
            'status_code' => 200,
            'duration_ms' => 15,
        ]);

        $csvPath = storage_path('app/test-export.csv');
        $this->artisan('api-watcher:export', ['--format' => 'csv', '--path' => $csvPath])->assertExitCode(0);

        expect(file_exists($csvPath))->toBeTrue();
        @unlink($csvPath);

        $this->artisan('api-watcher:export', ['--format' => 'xml'])->assertExitCode(1);
    }

    #[Test]
    public function command_monitor_executes_health_check()
    {
        ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'GET',
            'url' => 'http://localhost/error',
            'status_code' => 500,
            'duration_ms' => 2000,
        ]);

        $this->artisan('api-watcher:monitor')->assertExitCode(0);
    }

    #[Test]
    public function asset_manager_returns_asset_path()
    {
        $path = AssetManager::asset('js/app.js');
        expect($path)->toBeString();
    }
}
