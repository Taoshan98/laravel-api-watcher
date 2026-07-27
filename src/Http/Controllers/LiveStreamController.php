<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver;

class LiveStreamController extends Controller
{
    protected ApiWatcherStorageDriver $storage;

    public function __construct(ApiWatcherStorageDriver $storage)
    {
        $this->storage = $storage;
    }

    public function stream(Request $request): StreamedResponse
    {
        $sinceParam = $request->input('since');
        $dateFrom = is_string($sinceParam) ? $sinceParam : now()->subSeconds(10)->toDateTimeString();

        $response = new StreamedResponse(function () use ($dateFrom) {
            // Send initial ping event
            echo "event: ping\ndata: ".json_encode(['time' => now()->toIso8601String()])."\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            $newRequests = $this->storage->get([
                'date_from' => $dateFrom,
            ], 20, 0);

            if (count($newRequests) > 0) {
                echo "event: requests\ndata: ".json_encode($newRequests)."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}
