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

        if ($search = $request->input('search', $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%")
                    ->orWhere('method', 'like', "%{$search}%")
                    ->orWhere('request_body', 'like', "%{$search}%")
                    ->orWhere('response_body', 'like', "%{$search}%");
            });
        }

        if ($url = $request->input('url')) {
            $query->where('url', 'like', "%{$url}%");
        }

        if ($domain = $request->input('domain')) {
            $query->where('domain', 'like', "%{$domain}%");
        }

        if ($methodInput = $request->input('method')) {
            $methods = array_filter(array_map('strtoupper', (array) $methodInput));
            if (! empty($methods)) {
                $query->whereIn('method', $methods);
            }
        }

        if ($statusCodeInput = $request->input('status_code')) {
            $statusCodes = (array) $statusCodeInput;
            $query->where(function ($q) use ($statusCodes) {
                foreach ($statusCodes as $code) {
                    $codeStr = trim((string) $code);
                    if (str_ends_with($codeStr, 'xx')) {
                        $prefix = (int) substr($codeStr, 0, 1);
                        $q->orWhereBetween('status_code', [$prefix * 100, ($prefix * 100) + 99]);
                    } elseif ($codeStr === '0' || strtolower($codeStr) === 'failed') {
                        $q->orWhere('status_code', 0);
                    } elseif (is_numeric($codeStr)) {
                        $q->orWhere('status_code', (int) $codeStr);
                    }
                }
            });
        }

        if ($statusGroup = $request->input('status_group')) {
            if ($statusGroup === '2xx') {
                $query->whereBetween('status_code', [200, 299]);
            } elseif ($statusGroup === '4xx') {
                $query->whereBetween('status_code', [400, 499]);
            } elseif ($statusGroup === '5xx') {
                $query->whereBetween('status_code', [500, 599]);
            } elseif ($statusGroup === 'failed') {
                $query->where('status_code', 0);
            } elseif ($statusGroup === 'errors') {
                $query->where(function ($q) {
                    $q->where('status_code', '>=', 400)->orWhere('status_code', 0);
                });
            }
        }

        if ($parentId = $request->input('parent_request_id')) {
            $query->where('parent_request_id', 'like', "%{$parentId}%");
        }

        if ($request->has('duration_min') && $request->input('duration_min') !== '' && is_numeric($request->input('duration_min'))) {
            $query->where('duration_ms', '>=', (int) $request->input('duration_min'));
        }

        if ($request->has('duration_max') && $request->input('duration_max') !== '' && is_numeric($request->input('duration_max'))) {
            $query->where('duration_ms', '<=', (int) $request->input('duration_max'));
        }

        if ($dateFrom = $request->input('date_from')) {
            try {
                $query->where('created_at', '>=', \Carbon\Carbon::parse($dateFrom)->startOfDay());
            } catch (\Throwable) {
                $query->where('created_at', '>=', $dateFrom);
            }
        }

        if ($dateTo = $request->input('date_to')) {
            try {
                $query->where('created_at', '<=', \Carbon\Carbon::parse($dateTo)->endOfDay());
            } catch (\Throwable) {
                $query->where('created_at', '<=', $dateTo);
            }
        }

        $total = (clone $query)->count();

        $limit = (int) $request->input('limit', (int) $request->input('per_page', 50));
        $offset = (int) $request->input('offset', 0);

        if ($request->has('page')) {
            $page = max(1, (int) $request->input('page', 1));
            $offset = ($page - 1) * $limit;
        }

        $results = $query->latest('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json($results)->header('X-Total-Count', (string) $total);
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
