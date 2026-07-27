<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Taoshan98\LaravelApiWatcher\Models\ApiOutgoingRequest;

class OutgoingApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ApiOutgoingRequest::query();

        if ($domain = $request->input('domain')) {
            $query->where('domain', 'like', "%{$domain}%");
        }

        if ($method = $request->input('method')) {
            $query->where('method', strtoupper((string) $method));
        }

        if ($statusCode = $request->input('status_code')) {
            $query->where('status_code', (int) $statusCode);
        }

        if ($parentId = $request->input('parent_request_id')) {
            $query->where('parent_request_id', $parentId);
        }

        $limit = (int) $request->input('limit', 50);
        $offset = (int) $request->input('offset', 0);

        $results = $query->latest('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json($results);
    }

    public function show(string $id): JsonResponse
    {
        $outgoing = ApiOutgoingRequest::find($id);

        if (! $outgoing) {
            return response()->json(['message' => 'Outgoing request not found'], 404);
        }

        return response()->json($outgoing);
    }

    public function stats(): JsonResponse
    {
        $total = ApiOutgoingRequest::count();
        $errors = ApiOutgoingRequest::where(function ($q) {
            $q->where('status_code', '>=', 400)->orWhere('status_code', 0);
        })->count();

        $errorRate = $total > 0 ? round(($errors / $total) * 100, 1) : 0;
        $avgLatency = (int) round((float) ApiOutgoingRequest::avg('duration_ms'));

        $topDomains = ApiOutgoingRequest::select('domain', DB::raw('count(*) as count'), DB::raw('avg(duration_ms) as avg_duration'))
            ->groupBy('domain')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'domain' => $item->domain,
                    'count' => $item->count,
                    'avg_duration' => round((float) $item->avg_duration, 0),
                ];
            });

        return response()->json([
            'total_outgoing_requests' => $total,
            'error_rate' => $errorRate,
            'avg_latency' => $avgLatency,
            'top_domains' => $topDomains,
        ]);
    }
}
