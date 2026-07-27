<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Listeners;

use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Str;
use Taoshan98\LaravelApiWatcher\Models\ApiOutgoingRequest;
use Taoshan98\LaravelApiWatcher\Services\SensitiveDataRedactor;
use Throwable;

class CaptureOutgoingHttpRequest
{
    protected SensitiveDataRedactor $redactor;

    public function __construct(SensitiveDataRedactor $redactor)
    {
        $this->redactor = $redactor;
    }

    public function handleResponseReceived(ResponseReceived $event): void
    {
        if (! config('api-watcher.enabled', true)) {
            return;
        }

        try {
            $request = $event->request;
            $response = $event->response;
            $url = $request->url();
            $domain = parse_url($url, PHP_URL_HOST) ?: 'unknown';

            // Ignore internal API watcher or local dashboard calls to avoid feedback loops
            $dashboardPath = config('api-watcher.dashboard.path', 'api-watcher');
            if (str_contains($url, '/'.$dashboardPath)) {
                return;
            }

            $stats = $response->transferStats;
            $durationMs = $stats ? (int) round($stats->getTransferTime() * 1000) : 0;

            $parentRequestId = request()->attributes->get('api_watcher_request_id');

            ApiOutgoingRequest::create([
                'id' => (string) Str::uuid(),
                'parent_request_id' => $parentRequestId ? (string) $parentRequestId : null,
                'domain' => $domain,
                'method' => $request->method(),
                'url' => $url,
                'status_code' => $response->status(),
                'duration_ms' => $durationMs,
                'request_headers' => $this->redactor->redactArray($request->headers()),
                'request_body' => Str::limit($this->redactor->redactString((string) $request->body()), 64000),
                'response_headers' => $this->redactor->redactArray($response->headers()),
                'response_body' => Str::limit((string) $response->body(), 64000),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Fail silently to never break HTTP client calls
        }
    }

    public function handleConnectionFailed(ConnectionFailed $event): void
    {
        if (! config('api-watcher.enabled', true)) {
            return;
        }

        try {
            $request = $event->request;
            $url = $request->url();
            $domain = parse_url($url, PHP_URL_HOST) ?: 'unknown';

            $parentRequestId = request()->attributes->get('api_watcher_request_id');

            ApiOutgoingRequest::create([
                'id' => (string) Str::uuid(),
                'parent_request_id' => $parentRequestId ? (string) $parentRequestId : null,
                'domain' => $domain,
                'method' => $request->method(),
                'url' => $url,
                'status_code' => 0,
                'duration_ms' => 0,
                'request_headers' => $this->redactor->redactArray($request->headers()),
                'request_body' => Str::limit($this->redactor->redactString((string) $request->body()), 64000),
                'exception_info' => 'Connection Failed',
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Fail silently
        }
    }
}
