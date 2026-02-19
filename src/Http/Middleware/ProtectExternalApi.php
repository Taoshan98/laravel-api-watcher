<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectExternalApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('api-watcher.api.enabled', false)) {
            return response()->json(['message' => 'API is disabled.'], 403);
        }

        $headerName = config('api-watcher.api.header', 'X-API-WATCHER-KEY');
        $requestKey = $request->header($headerName);

        if (!$requestKey) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $token = \Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey::findToken($requestKey);

        if (!$token) {
            return response()->json(['message' => 'Invalid API Key.'], 401);
        }

        $token->update(['last_used_at' => now()]);

        return $next($request);
    }
}
