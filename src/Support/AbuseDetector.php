<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Support;

use Illuminate\Support\Facades\DB;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;

class AbuseDetector
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function detectSuspiciousIPs(int $minutes = 60, int $threshold = 100): array
    {
        return ApiRequest::select('ip_address', DB::raw('count(*) as total_requests'), DB::raw('sum(case when status_code in (401, 403, 429) then 1 else 0 end) as error_count'))
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->having('total_requests', '>=', $threshold)
            ->orderBy('total_requests', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'ip_address' => $item->ip_address,
                    'total_requests' => $item->total_requests,
                    'error_count' => $item->error_count,
                    'is_suspicious' => ($item->error_count / max($item->total_requests, 1)) >= 0.3,
                ];
            })
            ->toArray();
    }
}
