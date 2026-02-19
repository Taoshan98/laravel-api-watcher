<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiWatcherKey extends Model
{
    protected $guarded = [];

    protected $casts = [
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

    /**
     * Create a new key and return the plain text token.
     *
     * @param string $name
     * @return string The plain text token (id|secret)
     */
    public static function createKey(string $name): string
    {
        $secret = Str::random(40);
        
        $key = self::create([
            'name' => $name,
            'token' => hash('sha256', $secret),
        ]);

        return $key->id . '|' . $secret;
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

        return $this->id . '|' . $secret;
    }

    /**
     * Validate a plain text token.
     *
     * @param string $plainTextToken
     * @return self|null
     */
    public static function findToken(string $plainTextToken): ?self
    {
        if (!str_contains($plainTextToken, '|')) {
            return null;
        }

        [$id, $secret] = explode('|', $plainTextToken, 2);

        $instance = self::find($id);

        if (!$instance) {
            return null;
        }

        if (hash('sha256', $secret) === $instance->token) {
            return $instance;
        }

        return null;
    }
}
