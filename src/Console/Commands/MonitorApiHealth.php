<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Console\Commands;

use Illuminate\Console\Command;
use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;
use Illuminate\Support\Facades\Notification;
use Taoshan98\LaravelApiWatcher\Notifications\ApiHealthAlert;
use Illuminate\Support\Facades\Cache;

class MonitorApiHealth extends Command
{
    protected $signature = 'api-watcher:monitor';
    protected $description = 'Monitor API health metrics and send alerts if thresholds are exceeded';

    public function handle(ApiWatcherStorageDriver $storage): int
    {
        if (!config('api-watcher.alerts.enabled')) {
            $this->info('Alerts are disabled in configuration.');
            return Command::SUCCESS;
        }

        $interval = config('api-watcher.alerts.check_interval_minutes', 5);
        $startTime = now()->subMinutes($interval);

        $requests = ApiRequest::where('created_at', '>=', $startTime)->get();

        if ($requests->isEmpty()) {
            $this->info('No requests in the last interval.');
            return Command::SUCCESS;
        }

        $total = $requests->count();
        $errors = $requests->where('status_code', '>=', 500)->count(); // Focus on 5xx for alerts usually
        $avgLatency = $requests->avg('duration_ms');

        $errorRate = round(($errors / $total) * 100, 2);
        
        $errorThreshold = config('api-watcher.alerts.thresholds.error_rate', 5.0);
        $latencyThreshold = config('api-watcher.alerts.thresholds.high_latency_ms', 1000);

        $triggered = false;
        $reasons = [];

        if ($errorRate >= $errorThreshold) {
            $triggered = true;
            $reasons[] = "Error Rate {$errorRate}% >= {$errorThreshold}%";
        }

        if ($avgLatency >= $latencyThreshold) {
            $triggered = true;
            $reasons[] = "Latency {$avgLatency}ms >= {$latencyThreshold}ms";
        }

        if ($triggered) {
            // Cooldown check (prevent spamming every minute)
            if (Cache::has('api-watcher:alert-cooldown')) {
                $this->info('Alert triggered but cooldown is active.');
                //return Command::SUCCESS;
            }

            $metrics = [
                'error_rate' => $errorRate,
                'avg_latency' => round($avgLatency, 0),
                'total_requests' => $total,
                'interval_minutes' => $interval
            ];

            // Send notification
            // We need a notifiable entity. For simplicity, we can use an anonymous notifiable
            // or route to specific emails.
            $mailTo = config('api-watcher.alerts.notifications.mail.to');
            
            if ($mailTo) {
                 Notification::route('mail', $mailTo)
                    ->notify(new ApiHealthAlert($metrics));
            }
            
            // Set cooldown for 30 minutes
            Cache::put('api-watcher:alert-cooldown', true, now()->addMinutes(10));

            $this->error('Alert triggered: ' . implode(', ', $reasons));
        } else {
            $this->info('Health check passed.');
        }

        return Command::SUCCESS;
    }
}
