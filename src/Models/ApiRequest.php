<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
