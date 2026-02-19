<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Taoshan98\LaravelApiWatcher\Services\RequestCaptureService;
use Illuminate\Support\Str;

class CaptureApiRequest
{
    protected RequestCaptureService $captureService;

    public function __construct(RequestCaptureService $captureService)
    {
        $this->captureService = $captureService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->shouldCapture($request)) {
            return $next($request);
        }

        $startTime = microtime(true);

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $this->captureService->capture($request, null, $startTime, $e); // Capture exception state
            throw $e;
        }

        $this->captureService->capture($request, $response, $startTime);

        return $response;
    }

    protected function shouldCapture(Request $request): bool
    {
        if (!config('api-watcher.enabled', true)) {
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
