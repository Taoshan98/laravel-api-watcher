<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Support;

use Taoshan98\LaravelApiWatcher\Models\ApiRequest;

class RequestDiff
{
    /**
     * @return array<string, mixed>
     */
    public static function compare(ApiRequest $req1, ApiRequest $req2): array
    {
        $body1 = json_decode((string) $req1->request_body, true) ?? [];
        $body2 = json_decode((string) $req2->request_body, true) ?? [];

        $resp1 = json_decode((string) $req1->response_body, true) ?? [];
        $resp2 = json_decode((string) $req2->response_body, true) ?? [];

        return [
            'request1' => [
                'id' => $req1->id,
                'status_code' => $req1->status_code,
                'duration_ms' => $req1->duration_ms,
                'created_at' => (string) $req1->created_at,
            ],
            'request2' => [
                'id' => $req2->id,
                'status_code' => $req2->status_code,
                'duration_ms' => $req2->duration_ms,
                'created_at' => (string) $req2->created_at,
            ],
            'differences' => [
                'status_code_changed' => $req1->status_code !== $req2->status_code,
                'duration_delta_ms' => $req2->duration_ms - $req1->duration_ms,
                'body_added_keys' => is_array($body1) && is_array($body2) ? array_values(array_diff(array_keys($body2), array_keys($body1))) : [],
                'body_removed_keys' => is_array($body1) && is_array($body2) ? array_values(array_diff(array_keys($body1), array_keys($body2))) : [],
                'response_added_keys' => is_array($resp1) && is_array($resp2) ? array_values(array_diff(array_keys($resp2), array_keys($resp1))) : [],
                'response_removed_keys' => is_array($resp1) && is_array($resp2) ? array_values(array_diff(array_keys($resp1), array_keys($resp2))) : [],
            ],
        ];
    }
}
