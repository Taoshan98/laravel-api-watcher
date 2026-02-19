<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;
use Illuminate\Http\JsonResponse;

class ApiWatcherController extends Controller
{
    protected ApiWatcherStorageDriver $storage;

    public function __construct(ApiWatcherStorageDriver $storage)
    {
        $this->storage = $storage;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'method', 'status_code', 'url', 'ip_address', 'user_id', 
            'date_from', 'date_to', 'duration_min', 'duration_max'
        ]);
        $limit = (int) $request->input('limit', 50);
        $offset = (int) $request->input('offset', 0);

        $requests = $this->storage->get($filters, $limit, $offset);

        return response()->json($requests);
    }

    public function show(string $id): JsonResponse
    {
        $request = $this->storage->find($id);

        if (!$request) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        return response()->json($request);
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->storage->getStats());
    }

    public function analytics(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);
        
        return response()->json([
            'requests_per_day' => $this->storage->getRequestsPerDay($days),
            'error_rate_trend' => $this->storage->getErrorRateTrend($days),
            'status_code_distribution' => $this->storage->getStatusCodeDistribution($days),
            'top_slowest_routes' => $this->storage->getTopSlowestRoutes($days),
        ]);
    }

    public function config(): JsonResponse
    {
        $version = 'unknown';
        $composerPath = __DIR__ . '/../../../composer.json';
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            $version = $composer['version'] ?? 'unknown';
        }

        return response()->json([
            'version' => $version,
            'enabled' => config('api-watcher.enabled'),
            'record_requests' => config('api-watcher.capture.enabled'),
            'failed_only' => config('api-watcher.capture.failed_only'),
            'pruning_days' => config('api-watcher.storage.prune_after_days'),
            'storage_driver' => config('api-watcher.storage.driver'),
            'sensitive_fields' => config('api-watcher.hide_parameters'),
            'alerts_enabled' => config('api-watcher.alerts.enabled'),
            'alerts_interval' => config('api-watcher.alerts.check_interval_minutes'),
            'alerts_threshold_error' => config('api-watcher.alerts.thresholds.error_rate'),
            'alerts_threshold_latency' => config('api-watcher.alerts.thresholds.high_latency_ms'),
            'alerts_channels' => config('api-watcher.alerts.channels'),
        ]);
    }

    public function prune(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', config('api-watcher.storage.prune_after_days', 30));
        $this->storage->prune($days);
        return response()->json(['message' => 'Old logs pruned successfully.']);
    }

    public function clear(): JsonResponse
    {
        $this->storage->clear();
        return response()->json(['message' => 'All logs cleared successfully.']);
    }

    public function testAlert(): JsonResponse
    {
        if (!config('api-watcher.alerts.enabled')) {
            return response()->json(['message' => 'Alerts are disabled.'], 400);
        }

        $metrics = [
            'error_rate' => 99.9, // Fake high value for test
            'avg_latency' => 5000,
            'total_requests' => 100,
            'interval_minutes' => 5
        ];

        $mailTo = config('api-watcher.alerts.notifications.mail.to');
            
        if ($mailTo) {
             \Illuminate\Support\Facades\Notification::route('mail', $mailTo)
                ->notify(new \Taoshan98\LaravelApiWatcher\Notifications\ApiHealthAlert($metrics));
        }

        return response()->json(['message' => 'Test alert sent.']);
    }

    // --- API Key Management (Internal) ---

    public function indexKeys(): JsonResponse
    {
        $keys = \Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey::latest()->get();
        return response()->json($keys);
    }

    public function storeKey(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        
        $plainTextToken = \Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey::createKey($request->name);

        return response()->json([
            'message' => 'Key created successfully.',
            'token' => $plainTextToken
        ]);
    }

    public function updateKey(Request $request, $id): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        
        $key = \Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey::findOrFail($id);
        $key->update(['name' => $request->name]);

        return response()->json(['message' => 'Key updated successfully.']);
    }

    public function refreshKey($id): JsonResponse
    {
        $key = \Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey::findOrFail($id);
        $newToken = $key->regenerate();

        return response()->json([
            'message' => 'Key regenerated successfully.',
            'token' => $newToken
        ]);
    }

    public function destroyKey($id): JsonResponse
    {
        \Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey::destroy($id);
        return response()->json(['message' => 'Key deleted.']);
    }
}
