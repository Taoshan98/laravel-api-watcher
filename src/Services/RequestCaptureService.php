<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
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

    public function capture(Request $request, ?Response $response, float $startTime, ?Throwable $exception = null): void
    {
        try {
            $duration = round((microtime(true) - $startTime) * 1000);
            
            $data = [
                'id' => (string) Str::uuid(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'duration_ms' => (int) $duration,
                'ip_address' => $request->ip(),
                'user_id' => $request->user()?->getAuthIdentifier(),
                'route_name' => $request->route()?->getName(),
                'controller_action' => $request->route()?->getActionName(),
                'request_headers' => $this->redactor->redactArray($request->headers->all()),
                'request_body' => $this->getRequestBody($request),
                'response_headers' => $response ? $this->redactor->redactArray($response->headers->all()) : null,
                'response_body' => $response ? $this->getResponseBody($response) : null,
                'status_code' => $response ? $response->getStatusCode() : 500,
                'exception_info' => $exception ? $this->formatException($exception) : null,
                'memory_usage_kb' => (int) (memory_get_peak_usage(true) / 1024),
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
            Log::error('Laravel API Watcher failed to capture request: ' . $e->getMessage());
        }
    }

    protected function getRequestBody(Request $request): ?string
    {
        if (!config('api-watcher.capture.capture_request_body', true)) {
            return null;
        }

        $body = $request->getContent(); // Raw body
        
        // If JSON, structure it for redaction
        if ($request->isJson()) {
             $json = $request->json()->all();
             $redacted = $this->redactor->redactArray($json);
             return json_encode($redacted);
        }

        // For other types, return raw (redaction limited here for now)
        return Str::limit((string) $body, 64000);
    }

    protected function getResponseBody(Response $response): ?string
    {
        if (!config('api-watcher.capture.capture_response_body', true)) {
            return null;
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
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())->take(5)->toArray(), // Limit trace
        ]);
    }
}
