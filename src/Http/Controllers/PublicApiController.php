<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;

class PublicApiController extends Controller
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

        // Limit maximum records per request for safety
        if ($limit > 100) {
            $limit = 100;
        }

        $requests = $this->storage->get($filters, $limit, $offset);

        return response()->json([
            'data' => $requests,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
            ]
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $request = $this->storage->find($id);

        if (!$request) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        return response()->json(['data' => $request]);
    }

    public function stats(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);

        return response()->json([
            'data' => [
                'overview' => $this->storage->getStats(),
                'requests_per_day' => $this->storage->getRequestsPerDay($days),
                'error_rate_trend' => $this->storage->getErrorRateTrend($days),
            ]
        ]);
    }
}
