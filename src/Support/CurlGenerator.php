<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Support;

use Taoshan98\LaravelApiWatcher\Models\ApiRequest;

class CurlGenerator
{
    public static function generate(ApiRequest $request): string
    {
        $parts = ['curl', '-X', strtoupper($request->method)];

        $headers = $request->request_headers ?? [];
        if (is_array($headers)) {
            foreach ($headers as $key => $values) {
                if (in_array(strtolower((string) $key), ['host', 'content-length'])) {
                    continue;
                }
                $valStr = is_array($values) ? implode(', ', $values) : (string) $values;
                $parts[] = '-H';
                $parts[] = escapeshellarg("{$key}: {$valStr}");
            }
        }

        if (! empty($request->request_body)) {
            $parts[] = '-d';
            $parts[] = escapeshellarg($request->request_body);
        }

        $parts[] = escapeshellarg($request->url);

        return implode(' ', $parts);
    }
}
