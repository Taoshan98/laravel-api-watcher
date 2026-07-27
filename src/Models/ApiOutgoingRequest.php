<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $parent_request_id
 * @property string $domain
 * @property string $method
 * @property string $url
 * @property int $status_code
 * @property int $duration_ms
 * @property array|null $request_headers
 * @property string|null $request_body
 * @property array|null $response_headers
 * @property string|null $response_body
 * @property string|null $exception_info
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ApiOutgoingRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|ApiOutgoingRequest where(string|\Closure|array $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiOutgoingRequest select(mixed ...$columns)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiOutgoingRequest orderBy(string $column, string $direction = 'asc')
 * @method static ApiOutgoingRequest create(array $attributes = [])
 * @method static bool insert(array $values)
 * @method static ApiOutgoingRequest|null find(string $id)
 * @method static int count()
 * @method static float avg(string $column)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiOutgoingRequest distinct(mixed $column = null)
 * @method static void truncate()
 */
class ApiOutgoingRequest extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'request_headers' => 'array',
        'response_headers' => 'array',
        'duration_ms' => 'integer',
        'status_code' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getConnectionName()
    {
        return config('api-watcher.storage.connection') ?? parent::getConnectionName();
    }

    public function getTable()
    {
        return 'api_watcher_outgoing_requests';
    }
}
