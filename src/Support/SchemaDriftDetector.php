<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Support;

use Taoshan98\LaravelApiWatcher\Models\ApiRequest;

class SchemaDriftDetector
{
    /**
     * @return array<string, mixed>
     */
    public static function extractSchemaStructure(?string $jsonContent): array
    {
        if (! $jsonContent) {
            return [];
        }

        $decoded = json_decode($jsonContent, true);
        if (! is_array($decoded)) {
            return ['type' => gettype($decoded)];
        }

        return static::describeArraySchema($decoded);
    }

    /**
     * @param  array<string, mixed>  $array
     * @return array<string, mixed>
     */
    protected static function describeArraySchema(array $array): array
    {
        $structure = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $structure[$key] = static::describeArraySchema($value);
            } else {
                $structure[$key] = gettype($value);
            }
        }

        return $structure;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function detectDrifts(string $routeOrUrl): array
    {
        $requests = ApiRequest::where('url', 'like', "%{$routeOrUrl}%")
            ->where('status_code', 200)
            ->latest('created_at')
            ->limit(50)
            ->get();

        if ($requests->count() < 2) {
            return [];
        }

        $schemas = [];
        $drifts = [];

        foreach ($requests as $req) {
            /** @var ApiRequest $req */
            $schema = static::extractSchemaStructure($req->response_body);
            $hash = md5(json_encode($schema));

            if (! isset($schemas[$hash])) {
                $schemas[$hash] = [
                    'schema' => $schema,
                    'first_seen' => (string) $req->created_at,
                    'sample_request_id' => $req->id,
                ];
            }
        }

        if (count($schemas) > 1) {
            $drifts[] = [
                'route' => $routeOrUrl,
                'variations_count' => count($schemas),
                'variations' => array_values($schemas),
            ];
        }

        return $drifts;
    }
}
