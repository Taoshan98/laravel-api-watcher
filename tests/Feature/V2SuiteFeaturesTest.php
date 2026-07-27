<?php

namespace Taoshan98\LaravelApiWatcher\Tests\Feature;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;
use Taoshan98\LaravelApiWatcher\Support\RequestDiff;
use Taoshan98\LaravelApiWatcher\Support\SchemaDriftDetector;
use Taoshan98\LaravelApiWatcher\Tests\TestCase;

class V2SuiteFeaturesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->withoutMiddleware([\Illuminate\Auth\Middleware\Authenticate::class]);
        Config::set('api-watcher.enabled', true);
    }

    #[Test]
    public function export_postman_collection_command_creates_file()
    {
        ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'POST',
            'url' => 'http://localhost/api/users',
            'status_code' => 201,
            'duration_ms' => 45,
            'request_body' => json_encode(['name' => 'John']),
        ]);

        $testPath = storage_path('app/test-postman.json');
        $this->artisan('api-watcher:export-postman', ['--path' => $testPath])->assertExitCode(0);

        expect(file_exists($testPath))->toBeTrue();
        $content = json_decode(file_get_contents($testPath), true);
        expect($content['info']['name'])->toBe('Laravel API Watcher Collection');

        @unlink($testPath);
    }

    #[Test]
    public function generate_sla_report_command_creates_report()
    {
        ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'GET',
            'url' => 'http://localhost/api/health',
            'status_code' => 200,
            'duration_ms' => 12,
        ]);

        $testPath = storage_path('app/test-sla-report.json');
        $this->artisan('api-watcher:report', ['--days' => 7, '--path' => $testPath])->assertExitCode(0);

        expect(file_exists($testPath))->toBeTrue();
        $report = json_decode(file_get_contents($testPath), true);
        expect($report['sla_metrics']['uptime_percentage'])->toEqual(100.0);

        @unlink($testPath);
    }

    #[Test]
    public function request_diff_detects_differences()
    {
        $req1 = ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'POST',
            'url' => 'http://localhost/api/test',
            'status_code' => 200,
            'duration_ms' => 50,
            'request_body' => json_encode(['a' => 1]),
        ]);

        $req2 = ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'POST',
            'url' => 'http://localhost/api/test',
            'status_code' => 500,
            'duration_ms' => 150,
            'request_body' => json_encode(['a' => 1, 'b' => 2]),
        ]);

        $diff = RequestDiff::compare($req1, $req2);

        expect($diff['differences']['status_code_changed'])->toBeTrue();
        expect($diff['differences']['duration_delta_ms'])->toBe(100);
        expect($diff['differences']['body_added_keys'])->toContain('b');
    }

    #[Test]
    public function schema_drift_detector_identifies_structure_changes()
    {
        ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'GET',
            'url' => 'http://localhost/api/v1/user',
            'status_code' => 200,
            'duration_ms' => 10,
            'response_body' => json_encode(['id' => 1, 'name' => 'Alice']),
            'created_at' => now()->subMinutes(10),
        ]);

        ApiRequest::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'method' => 'GET',
            'url' => 'http://localhost/api/v1/user',
            'status_code' => 200,
            'duration_ms' => 12,
            'response_body' => json_encode(['id' => 1, 'name' => 'Alice', 'role' => 'admin']),
            'created_at' => now(),
        ]);

        $drifts = SchemaDriftDetector::detectDrifts('api/v1/user');
        expect(count($drifts))->toBeGreaterThan(0);
        expect($drifts[0]['variations_count'])->toBe(2);
    }

    #[Test]
    public function diagnostics_endpoint_returns_trend_and_abuse_data()
    {
        $response = $this->getJson('/api-watcher/api/diagnostics');
        $response->assertOk()
            ->assertJsonStructure(['latency_trend', 'suspicious_ips']);
    }
}
