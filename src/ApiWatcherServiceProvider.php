<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher;

use Illuminate\Support\ServiceProvider;

class ApiWatcherServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/api-watcher.php', 'api-watcher');

        $this->app->bind(Contracts\ApiWatcherStorageDriver::class, function ($app) {
            $driver = config('api-watcher.storage.driver', 'database');

            return match ($driver) {
                'database' => new Services\Storage\DatabaseDriver,
                'null' => new Services\Storage\NullDriver,
                'redis' => new Services\Storage\RedisDriver,
                default => new Services\Storage\DatabaseDriver,
            };
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/api-watcher.php' => config_path('api-watcher.php'),
            ], 'api-watcher-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'api-watcher-migrations');

            $this->publishes([
                __DIR__.'/../dist' => public_path('vendor/api-watcher'),
            ], 'api-watcher-assets');

            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->commands([
                Console\Commands\FakeApiRequests::class,
                Console\Commands\PruneApiRequests::class,
                Console\Commands\ExportApiRequests::class,
                Console\Commands\ExportPostmanCollection::class,
                Console\Commands\GenerateSlaReport::class,
                Console\Commands\ClearApiRequests::class,
                Console\Commands\FlushApiRequestsBuffer::class,
                Console\Commands\MonitorApiHealth::class,
                Console\Commands\CreateApiKey::class,
                Console\Commands\ListApiKeys::class,
                Console\Commands\RenameApiKey::class,
                Console\Commands\RegenerateApiKey::class,
                Console\Commands\DeleteApiKey::class,
            ]);
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'api-watcher');

        // Register routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Authorization Gate
        \Illuminate\Support\Facades\Gate::define('viewApiWatcher', function ($user = null) {
            return app()->environment('local', 'testing');
        });

        // Auto-register middleware
        if (config('api-watcher.capture.auto_inject_middleware', true)) {
            $this->registerMiddleware();
        }

        // Register Outgoing HTTP request listeners
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Http\Client\Events\ResponseReceived::class,
            [\Taoshan98\LaravelApiWatcher\Listeners\CaptureOutgoingHttpRequest::class, 'handleResponseReceived']
        );
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Http\Client\Events\ConnectionFailed::class,
            [\Taoshan98\LaravelApiWatcher\Listeners\CaptureOutgoingHttpRequest::class, 'handleConnectionFailed']
        );
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('api', Http\Middleware\CaptureApiRequest::class);
    }
}
