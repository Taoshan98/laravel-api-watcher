<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;

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
            'search', 'q', 'method', 'status_code', 'url', 'ip_address', 'user_id',
            'date_from', 'date_to', 'duration_min', 'duration_max',
        ]);

        if (empty($filters['search']) && ! empty($filters['q'])) {
            $filters['search'] = $filters['q'];
        }

        $perPage = (int) ($request->input('per_page') ?? $request->input('limit') ?? 50);
        if ($perPage < 1) {
            $perPage = 50;
        }

        $page = (int) $request->input('page', 1);
        if ($request->has('offset')) {
            $offset = (int) $request->input('offset', 0);
            $page = (int) floor($offset / $perPage) + 1;
        } else {
            $offset = max(0, ($page - 1) * $perPage);
        }

        $total = $this->storage->count($filters);
        $requests = $this->storage->get($filters, $perPage, $offset);

        return response()->json([
            'data' => $requests,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $request = $this->storage->find($id);

        if (! $request) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        return response()->json($request);
    }

    public function curl(string $id): JsonResponse
    {
        $request = $this->storage->find($id);

        if (! $request) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        $command = \Taoshan98\LaravelApiWatcher\Support\CurlGenerator::generate($request);

        return response()->json([
            'id' => $id,
            'curl' => $command,
        ]);
    }

    public function replay(string $id): JsonResponse
    {
        $apiRequest = $this->storage->find($id);

        if (! $apiRequest) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        try {
            $headers = $apiRequest->request_headers ?? [];
            if (is_array($headers)) {
                unset($headers['host'], $headers['content-length']);
            } else {
                $headers = [];
            }

            $http = \Illuminate\Support\Facades\Http::withHeaders($headers);

            $method = strtolower($apiRequest->method);
            $url = $apiRequest->url;
            $body = $apiRequest->request_body;

            $response = match ($method) {
                'post' => $http->withBody($body ?? '', 'application/json')->post($url),
                'put' => $http->withBody($body ?? '', 'application/json')->put($url),
                'patch' => $http->withBody($body ?? '', 'application/json')->patch($url),
                'delete' => $http->delete($url),
                default => $http->get($url),
            };

            return response()->json([
                'status_code' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->json() ?? $response->body(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to replay request: '.$e->getMessage(),
            ], 500);
        }
    }

    public function diff(Request $request): JsonResponse
    {
        $request->validate([
            'id1' => 'required|string',
            'id2' => 'required|string',
        ]);

        $req1 = $this->storage->find($request->input('id1'));
        $req2 = $this->storage->find($request->input('id2'));

        if (! $req1 || ! $req2) {
            return response()->json(['message' => 'One or both requests not found.'], 404);
        }

        $comparison = \Taoshan98\LaravelApiWatcher\Support\RequestDiff::compare($req1, $req2);

        return response()->json($comparison);
    }

    public function waterfall(string $id): JsonResponse
    {
        $req = $this->storage->find($id);

        if (! $req) {
            return response()->json(['message' => 'Request not found.'], 404);
        }

        $outgoing = \Taoshan98\LaravelApiWatcher\Models\ApiOutgoingRequest::where('parent_request_id', $id)->get();

        return response()->json([
            'request_id' => $id,
            'duration_ms' => $req->duration_ms,
            'query_count' => $req->query_count ?? 0,
            'query_time_ms' => $req->query_time_ms ?? 0,
            'outgoing_requests_count' => $outgoing->count(),
            'outgoing_requests' => $outgoing,
        ]);
    }

    public function schemaDrifts(Request $request): JsonResponse
    {
        $url = (string) $request->input('url', '');
        $drifts = \Taoshan98\LaravelApiWatcher\Support\SchemaDriftDetector::detectDrifts($url);

        return response()->json($drifts);
    }

    public function diagnostics(): JsonResponse
    {
        $trend = \Taoshan98\LaravelApiWatcher\Support\TrendAnalyzer::analyzeLatencyTrend(7);
        $suspicious = \Taoshan98\LaravelApiWatcher\Support\AbuseDetector::detectSuspiciousIPs(60, 50);

        return response()->json([
            'latency_trend' => $trend,
            'suspicious_ips' => $suspicious,
        ]);
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
        $version = \Taoshan98\LaravelApiWatcher\ApiWatcher::version();

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
        if (! config('api-watcher.alerts.enabled')) {
            return response()->json(['message' => 'Alerts are disabled.'], 400);
        }

        $metrics = [
            'error_rate' => 99.9, // Fake high value for test
            'avg_latency' => 5000,
            'total_requests' => 100,
            'interval_minutes' => 5,
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scopes' => 'nullable|array',
        ]);

        $name = (string) $validated['name'];
        $scopes = $validated['scopes'] ?? ['*'];

        $plainTextToken = \Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey::createKey($name, $scopes);

        return response()->json([
            'message' => 'Key created successfully.',
            'token' => $plainTextToken,
        ]);
    }

    public function updateKey(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scopes' => 'nullable|array',
        ]);

        $key = \Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey::findOrFail($id);
        $key->update($validated);

        return response()->json(['message' => 'Key updated successfully.']);
    }

    public function refreshKey($id): JsonResponse
    {
        $key = \Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey::findOrFail($id);
        $newToken = $key->regenerate();

        return response()->json([
            'message' => 'Key regenerated successfully.',
            'token' => $newToken,
        ]);
    }

    public function destroyKey($id): JsonResponse
    {
        \Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey::destroy($id);

        return response()->json(['message' => 'Key deleted.']);
    }
}
