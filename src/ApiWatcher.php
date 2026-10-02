<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher;

use Composer\InstalledVersions;

class ApiWatcher
{
    public const VERSION = '2.1.0';

    /**
     * Get the current version of Laravel API Watcher.
     */
    public static function version(): string
    {
        if (class_exists(InstalledVersions::class)) {
            try {
                $version = InstalledVersions::getPrettyVersion('taoshan98/laravel-api-watcher');
                if ($version && $version !== 'dev-main' && ! str_starts_with($version, 'dev-')) {
                    return ltrim($version, 'v');
                }
            } catch (\Throwable) {
                // Ignore and fall through to file/constant fallback
            }
        }

        $composerPath = __DIR__.'/../composer.json';
        if (file_exists($composerPath)) {
            $content = file_get_contents($composerPath);
            if ($content !== false) {
                $composer = json_decode($content, true);
                if (is_array($composer) && ! empty($composer['version'])) {
                    return ltrim((string) $composer['version'], 'v');
                }
            }
        }

        return self::VERSION;
    }
}
