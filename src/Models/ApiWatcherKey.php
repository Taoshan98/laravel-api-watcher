<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int|string $id
 * @property string $name
 * @property string $token
 * @property array|null $scopes
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static ApiWatcherKey create(array $attributes = [])
 * @method static ApiWatcherKey|null find(mixed $id)
 * @method static ApiWatcherKey findOrFail(mixed $id)
 * @method static \Illuminate\Database\Eloquent\Builder|ApiWatcherKey latest(string $column = 'created_at')
 * @method static int destroy(mixed $ids)
 */
class ApiWatcherKey extends Model
{
    protected $guarded = [];

    protected $casts = [
        'scopes' => 'array',
        'last_used_at' => 'datetime',
    ];

    public function getConnectionName()
    {
        return config('api-watcher.storage.connection') ?? parent::getConnectionName();
    }

    public function getTable()
    {
        return 'api_watcher_keys'; // Hardcoded as it's internal
    }

    public static function availableScopes(): array
    {
        return [
            '*' => 'Full Access',
            'read:stats' => 'Read Statistics',
            'read:requests' => 'Read Request Logs',
            'manage:keys' => 'Manage API Keys',
        ];
    }

    public function hasScope(string $scope): bool
    {
        if (empty($this->scopes) || in_array('*', $this->scopes, true)) {
            return true;
        }

        return in_array($scope, $this->scopes, true);
    }

    /**
     * Create a new key and return the plain text token.
     *
     * @return string The plain text token (id|secret)
     */
    public static function createKey(string $name, array $scopes = ['*']): string
    {
        $secret = Str::random(40);

        $key = self::create([
            'name' => $name,
            'token' => hash('sha256', $secret),
            'scopes' => $scopes,
        ]);

        return $key->id.'|'.$secret;
    }

    /**
     * Regenerate the key token.
     *
     * @return string The new plain text token (id|secret)
     */
    public function regenerate(): string
    {
        $secret = Str::random(40);

        $this->update([
            'token' => hash('sha256', $secret),
            'last_used_at' => null, // Reset usage stats on regen? Maybe keep it. Let's reset to indicate new lifecycle.
        ]);

        return $this->id.'|'.$secret;
    }

    /**
     * Validate a plain text token.
     */
    public static function findToken(string $plainTextToken): ?self
    {
        if (! str_contains($plainTextToken, '|')) {
            return null;
        }

        [$id, $secret] = explode('|', $plainTextToken, 2);

        $instance = self::find($id);

        if (! $instance) {
            return null;
        }

        if (hash('sha256', $secret) === $instance->token) {
            return $instance;
        }

        return null;
    }
}
