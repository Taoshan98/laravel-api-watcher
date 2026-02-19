<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Support;

class AssetManager
{
    protected static ?array $manifest = null;

    public static function asset(string $path): string
    {
        if (app()->environment('local') && file_exists(public_path('hot'))) {
            return $path; // Vite handle this via @vite
        }

        if (static::$manifest === null) {
            $manifestPath = public_path('vendor/api-watcher/.vite/manifest.json');
            
            if (file_exists($manifestPath)) {
                static::$manifest = json_decode(file_get_contents($manifestPath), true);
            } else {
                static::$manifest = [];
            }
        }

        $manifestKey = 'resources/js/app.js'; // entry point

        if (!isset(static::$manifest[$manifestKey])) {
            return asset('vendor/api-watcher/' . $path);
        }

        $asset = static::$manifest[$manifestKey];

        if ($path === 'resources/css/app.css' && isset($asset['css'][0])) {
            return asset('vendor/api-watcher/' . $asset['css'][0]);
        }

        if ($path === 'resources/js/app.js') {
            return asset('vendor/api-watcher/' . $asset['file']);
        }

        return asset('vendor/api-watcher/' . $path);
    }
}
