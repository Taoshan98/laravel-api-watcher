<?php

namespace Taoshan98\LaravelApiWatcher\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Taoshan98\LaravelApiWatcher\ApiWatcherServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            ApiWatcherServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:2fl+Ktvkfl+Fuz4Qp/yWCIyX667HC6O4+PSR4ctJ8U0=');
        
        /*
        $migration = include __DIR__.'/../database/migrations/create_api_watcher_requests_table.php';
        $migration->up();
        */
    }
}
