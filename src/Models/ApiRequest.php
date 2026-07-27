<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $method
 * @property string $url
 * @property int $status_code
 * @property int $duration_ms
 * @property string|null $ip_address
 * @property string|null $user_id
 * @property string|null $route_name
 * @property string|null $controller_action
 * @property array|null $request_headers
 * @property string|null $request_body
 * @property array|null $response_headers
 * @property string|null $response_body
 * @property string|null $exception_info
 * @property array|null $tags
 * @property int|null $memory_usage_kb
 * @property int|null $query_count
 * @property float|null $query_time_ms
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ApiRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|ApiRequest where(string|\Closure|array $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiRequest whereBetween(string $column, array $values)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiRequest select(mixed ...$columns)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiRequest orderBy(string $column, string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder|ApiRequest latest(string $column = 'created_at')
 * @method static ApiRequest create(array $attributes = [])
 * @method static bool insert(array $values)
 * @method static ApiRequest|null find(string $id)
 * @method static int count()
 * @method static float avg(string $column)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiRequest distinct(mixed $column = null)
 * @method static void truncate()
 */
class ApiRequest extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Taoshan98\LaravelApiWatcher\Database\Factories\ApiRequestFactory::new();
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (config('api-watcher.encryption.enabled')) {
            $this->mergeCasts([
                'request_headers' => 'encrypted:array',
                'response_headers' => 'encrypted:array',
                'request_body' => 'encrypted',
                'response_body' => 'encrypted',
            ]);
        }
    }

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'request_headers' => 'array',
        'response_headers' => 'array',
        'tags' => 'array',
        'duration_ms' => 'integer',
        'status_code' => 'integer',
        'memory_usage_kb' => 'integer',
        'query_count' => 'integer',
        'query_time_ms' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getConnectionName()
    {
        return config('api-watcher.storage.connection') ?? parent::getConnectionName();
    }

    public function getTable()
    {
        return config('api-watcher.storage.table', 'api_watcher_requests');
    }
}
