<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;
use Throwable;

class RequestCaptureService
{
    protected SensitiveDataRedactor $redactor;

    protected ApiWatcherStorageDriver $storage;

    public function __construct(SensitiveDataRedactor $redactor, ApiWatcherStorageDriver $storage)
    {
        $this->redactor = $redactor;
        $this->storage = $storage;
    }

    public function capture(Request $request, ?Response $response, float $startTime, ?Throwable $exception = null, int $queryCount = 0, float $queryTimeMs = 0.0): void
    {
        try {
            $duration = round((microtime(true) - $startTime) * 1000);
            $statusCode = $response ? $response->getStatusCode() : 500;

            if (! $this->shouldSample($statusCode, (float) $duration)) {
                return;
            }

            $route = $request->route();
            $routeName = $route instanceof \Illuminate\Routing\Route ? $route->getName() : null;
            $controllerAction = $route instanceof \Illuminate\Routing\Route ? $route->getActionName() : null;

            $requestId = (string) ($request->attributes->get('api_watcher_request_id') ?? Str::uuid());

            $data = [
                'id' => $requestId,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'duration_ms' => (int) $duration,
                'ip_address' => $request->ip(),
                'user_id' => $request->user()?->getAuthIdentifier(),
                'route_name' => $routeName,
                'controller_action' => $controllerAction,
                'request_headers' => $this->redactor->redactArray($request->headers->all()),
                'request_body' => $this->getRequestBody($request),
                'response_headers' => $response ? $this->redactor->redactArray($response->headers->all()) : null,
                'response_body' => $response ? $this->getResponseBody($response) : null,
                'status_code' => $statusCode,
                'exception_info' => $exception ? $this->formatException($exception) : null,
                'memory_usage_kb' => (int) (memory_get_peak_usage(true) / 1024),
                'query_count' => $queryCount,
                'query_time_ms' => round($queryTimeMs, 2),
                'created_at' => now(),
            ];

            if (config('api-watcher.capture.async', true)) {
                dispatch(function () use ($data) {
                    $this->storage->store($data);
                })->afterResponse();
            } else {
                $this->storage->store($data);
            }

        } catch (Throwable $e) {
            Log::error('Laravel API Watcher failed to capture request: '.$e->getMessage());
        }
    }

    protected function shouldSample(int $statusCode, float $durationMs): bool
    {
        if (! config('api-watcher.sampling.enabled', false)) {
            return true;
        }

        if ($statusCode >= 400 && config('api-watcher.sampling.always_sample_errors', true)) {
            return true;
        }

        $slowThreshold = config('api-watcher.sampling.always_sample_slow_ms', 500);
        if ($slowThreshold > 0 && $durationMs >= $slowThreshold) {
            return true;
        }

        $rate = (float) config('api-watcher.sampling.rate', 1.0);
        if ($rate >= 1.0) {
            return true;
        }
        if ($rate <= 0.0) {
            return false;
        }

        return (mt_rand() / mt_getrandmax()) <= $rate;
    }

    protected function getRequestBody(Request $request): ?string
    {
        if (! config('api-watcher.capture.capture_request_body', true)) {
            return null;
        }

        if ($request->isJson()) {
            $json = $request->json()->all();
            $redacted = $this->redactor->redactArray($json);

            return json_encode($redacted);
        }

        $allInputs = $request->all();
        if (! empty($allInputs)) {
            $redacted = $this->redactor->redactArray($allInputs);

            return json_encode($redacted);
        }

        $body = $request->getContent();

        return Str::limit((string) $body, 64000);
    }

    protected function getResponseBody(Response $response): ?string
    {
        if (! config('api-watcher.capture.capture_response_body', true)) {
            return null;
        }

        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse ||
            $response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return '[STREAMED/BINARY FILE]';
        }

        $content = $response->getContent();

        if ($content === false) {
            return null;
        }

        $limit = config('api-watcher.capture.max_response_body_size_kb', 64) * 1024;

        if (strlen($content) > $limit) {
            return '[TRUNCATED] Response body size exceeds limit.';
        }

        // Try to decode JSON to redact
        $json = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $redacted = $this->redactor->redactArray($json);

            return json_encode($redacted);
        }

        return $content;
    }

    protected function formatException(Throwable $e): string
    {
        return json_encode([
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())->take(15)->map(function ($frame) {
                return [
                    'file' => $frame['file'] ?? null,
                    'line' => $frame['line'] ?? null,
                    'function' => $frame['function'],
                    'class' => $frame['class'] ?? null,
                    'type' => $frame['type'] ?? null,
                ];
            })->values()->toArray(),
        ]);
    }
}
