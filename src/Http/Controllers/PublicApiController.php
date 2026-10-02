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
            'search', 'q', 'method', 'status_code', 'url', 'ip_address', 'user_id',
            'date_from', 'date_to', 'duration_min', 'duration_max',
        ]);

        if (empty($filters['search']) && ! empty($filters['q'])) {
            $filters['search'] = $filters['q'];
        }

        $limit = (int) ($request->input('per_page') ?? $request->input('limit') ?? 50);
        if ($limit > 100) {
            $limit = 100;
        } elseif ($limit < 1) {
            $limit = 50;
        }

        $page = (int) $request->input('page', 1);
        if ($request->has('offset')) {
            $offset = (int) $request->input('offset', 0);
            $page = (int) floor($offset / $limit) + 1;
        } else {
            $offset = max(0, ($page - 1) * $limit);
        }

        $total = $this->storage->count($filters);
        $requests = $this->storage->get($filters, $limit, $offset);

        return response()->json([
            'data' => $requests,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'last_page' => (int) ceil($total / $limit),
                'offset' => $offset,
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $request = $this->storage->find($id);

        if (! $request) {
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
            ],
        ]);
    }
}
