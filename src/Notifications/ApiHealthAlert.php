<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ApiHealthAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $metrics;

    public function __construct(array $metrics)
    {
        $this->metrics = $metrics;
    }

    public function via($notifiable): array
    {
        return config('api-watcher.alerts.channels', ['mail']);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('API Watcher Alert: Health Threshold Exceeded')
            ->greeting('API Health Alert')
            ->line('The following metrics have exceeded the configured thresholds:')
            ->line("Error Rate: {$this->metrics['error_rate']}% (Threshold: " . config('api-watcher.alerts.thresholds.error_rate') . "%)")
            ->line("Average Latency: {$this->metrics['avg_latency']}ms (Threshold: " . config('api-watcher.alerts.thresholds.high_latency_ms') . "ms)")
            ->line('Please investigate the issue immediately.')
            ->action('View Dashboard', url(config('api-watcher.dashboard.path')));
    }
}
