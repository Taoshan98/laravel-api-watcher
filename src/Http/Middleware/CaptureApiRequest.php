<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Taoshan98\LaravelApiWatcher\Services\RequestCaptureService;

class CaptureApiRequest
{
    protected RequestCaptureService $captureService;

    public function __construct(RequestCaptureService $captureService)
    {
        $this->captureService = $captureService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldCapture($request)) {
            return $next($request);
        }

        $requestId = (string) Str::uuid();
        $request->attributes->set('api_watcher_request_id', $requestId);

        $startTime = microtime(true);
        $queryCount = 0;
        $queryTimeMs = 0.0;

        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$queryCount, &$queryTimeMs) {
            $queryCount++;
            $queryTimeMs += (float) $query->time;
        });

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $this->captureService->capture($request, null, $startTime, $e, $queryCount, $queryTimeMs); // Capture exception state
            throw $e;
        }

        $this->captureService->capture($request, $response, $startTime, null, $queryCount, $queryTimeMs);

        return $response;
    }

    protected function shouldCapture(Request $request): bool
    {
        if (! config('api-watcher.enabled', true)) {
            return false;
        }

        $patterns = config('api-watcher.capture.match', ['api/*']);
        $ignoredPatterns = config('api-watcher.capture.ignore', []);

        foreach ($ignoredPatterns as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        foreach ($patterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
