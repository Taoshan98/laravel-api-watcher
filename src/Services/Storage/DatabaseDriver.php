<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Services\Storage;

use Illuminate\Support\Facades\DB;
use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;

class DatabaseDriver implements ApiWatcherStorageDriver
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): void
    {
        // Use direct DB insert for performance if model overhead is too high,
        // but Model is safer for casts. Let's use Model for now.
        ApiRequest::create($data);
    }

    /**
     * @param  array<int, array<string, mixed>>  $batch
     */
    public function storeBatch(array $batch): void
    {
        if (empty($batch)) {
            return;
        }

        ApiRequest::insert($batch);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function get(array $filters = [], int $limit = 50, int $offset = 0): mixed
    {
        $query = ApiRequest::query();

        if (! empty($filters['method'])) {
            $query->whereIn('method', (array) $filters['method']);
        }

        if (! empty($filters['status_code'])) {
            $statusCodes = (array) $filters['status_code'];
            $query->where(function ($q) use ($statusCodes) {
                foreach ($statusCodes as $code) {
                    if (str_ends_with((string) $code, 'xx')) {
                        $prefix = substr((string) $code, 0, 1);
                        $q->orWhereBetween('status_code', [$prefix.'00', $prefix.'99']);
                    } else {
                        $q->orWhere('status_code', $code);
                    }
                }
            });
        }

        if (! empty($filters['url'])) {
            $query->where('url', 'like', '%'.$filters['url'].'%');
        }

        if (! empty($filters['ip_address'])) {
            $query->where('ip_address', 'like', '%'.$filters['ip_address'].'%');
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['duration_min'])) {
            $query->where('duration_ms', '>=', $filters['duration_min']);
        }

        if (! empty($filters['duration_max'])) {
            $query->where('duration_ms', '<=', $filters['duration_max']);
        }

        return $query->latest('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function find(string $id): mixed
    {
        return ApiRequest::find($id);
    }

    public function prune(int $days): int
    {
        return ApiRequest::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $percentiles = $this->getP95P99Latency();

        return [
            'total_requests' => ApiRequest::count(),
            'error_rate' => $this->calculateErrorRate(),
            'avg_latency' => (int) ApiRequest::avg('duration_ms'),
            'p95_latency' => $percentiles['p95'],
            'p99_latency' => $percentiles['p99'],
            'active_users' => ApiRequest::distinct('user_id')->count('user_id'),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getP95P99Latency(): array
    {
        $durations = ApiRequest::orderBy('duration_ms', 'asc')->pluck('duration_ms')->toArray();
        $count = count($durations);

        if ($count === 0) {
            return ['p95' => 0, 'p99' => 0];
        }

        $p95Index = (int) min(floor($count * 0.95), $count - 1);
        $p99Index = (int) min(floor($count * 0.99), $count - 1);

        return [
            'p95' => (int) $durations[$p95Index],
            'p99' => (int) $durations[$p99Index],
        ];
    }

    protected function calculateErrorRate(): float
    {
        $total = ApiRequest::count();
        if ($total === 0) {
            return 0;
        }

        $errors = ApiRequest::where('status_code', '>=', 400)->count();

        return round(($errors / $total) * 100, 1);
    }

    /**
     * @return array<string, int>
     */
    public function getRequestsPerDay(int $days = 30): array
    {
        return ApiRequest::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    /**
     * @return array<string, float>
     */
    public function getErrorRateTrend(int $days = 30): array
    {
        // For error rate, we need total and errors per day
        $data = ApiRequest::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as total'),
            DB::raw('sum(case when status_code >= 400 then 1 else 0 end) as errors')
        )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $data->mapWithKeys(function ($item) {
            $rate = $item->total > 0 ? round(($item->errors / $item->total) * 100, 1) : 0;

            return [$item->date => $rate];
        })->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getStatusCodeDistribution(int $days = 30): array
    {
        return ApiRequest::select('status_code', DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('status_code')
            ->orderBy('count', 'desc')
            ->pluck('count', 'status_code')
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopSlowestRoutes(int $days = 30, int $limit = 10): array
    {
        return ApiRequest::select('url', DB::raw('avg(duration_ms) as avg_duration'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('url')
            ->orderBy('avg_duration', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'url' => str_replace(url('/'), '', $item->url), // Relative URL for display
                    'avg_duration' => round((float) $item->avg_duration, 0),
                ];
            })
            ->toArray();
    }

    public function clear(): void
    {
        ApiRequest::truncate();
    }
}
