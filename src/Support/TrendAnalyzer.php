<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Support;

use Taoshan98\LaravelApiWatcher\Models\ApiRequest;

class TrendAnalyzer
{
    /**
     * @return array<string, mixed>
     */
    public static function analyzeLatencyTrend(int $days = 7): array
    {
        $recentAvg = (float) ApiRequest::where('created_at', '>=', now()->subDays(1))->avg('duration_ms');
        $baselineAvg = (float) ApiRequest::whereBetween('created_at', [now()->subDays($days), now()->subDays(1)])->avg('duration_ms');

        if ($baselineAvg <= 0.0) {
            return [
                'has_degradation' => false,
                'delta_percentage' => 0.0,
                'recent_avg_ms' => round($recentAvg, 1),
                'baseline_avg_ms' => round($baselineAvg, 1),
            ];
        }

        $delta = round((($recentAvg - $baselineAvg) / $baselineAvg) * 100, 1);
        $hasDegradation = $delta >= 25.0; // Trigger if +25% slower than baseline

        return [
            'has_degradation' => $hasDegradation,
            'delta_percentage' => $delta,
            'recent_avg_ms' => round($recentAvg, 1),
            'baseline_avg_ms' => round($baselineAvg, 1),
        ];
    }
}
