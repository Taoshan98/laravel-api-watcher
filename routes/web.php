<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('api-watcher.dashboard.path', 'api-watcher').'/api/v1',
    'middleware' => ['api', \Taoshan98\LaravelApiWatcher\Http\Middleware\ProtectExternalApi::class],
    'as' => 'api-watcher.public-api.',
], function () {
    Route::get('/requests', [Taoshan98\LaravelApiWatcher\Http\Controllers\PublicApiController::class, 'index'])->name('requests.index');
    Route::get('/requests/{id}', [Taoshan98\LaravelApiWatcher\Http\Controllers\PublicApiController::class, 'show'])->name('requests.show');
    Route::get('/stats', [Taoshan98\LaravelApiWatcher\Http\Controllers\PublicApiController::class, 'stats'])->name('stats');
});

Route::group([
    'prefix' => config('api-watcher.dashboard.path', 'api-watcher'),
    'middleware' => config('api-watcher.dashboard.middleware', ['web', 'auth']),
    'as' => 'api-watcher.',
], function () {
    // API Endpoints for the dashboard (to fetch data)
    Route::group(['prefix' => 'api', 'as' => 'api.'], function () {
        Route::get('/stats', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'stats'])->name('stats');
        Route::get('/diagnostics', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'diagnostics'])->name('diagnostics');
        Route::get('/live-stream', [Taoshan98\LaravelApiWatcher\Http\Controllers\LiveStreamController::class, 'stream'])->name('live-stream');
        Route::get('/analytics', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'analytics'])->name('analytics');
        Route::get('/config', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'config'])->name('config');
        Route::post('/actions/prune', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'prune'])->name('actions.prune');
        Route::post('/actions/clear', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'clear'])->name('actions.clear');
        Route::post('/actions/test-alert', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'testAlert'])->name('actions.test-alert');
        Route::post('/requests/diff', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'diff'])->name('requests.diff');
        Route::get('/requests/{id}/waterfall', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'waterfall'])->name('requests.waterfall');
        Route::get('/schema-drifts', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'schemaDrifts'])->name('schema-drifts');
        Route::get('/requests', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'index'])->name('requests.index');
        Route::get('/requests/{id}', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'show'])->name('requests.show');
        Route::get('/requests/{id}/curl', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'curl'])->name('requests.curl');
        Route::post('/requests/{id}/replay', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'replay'])->name('requests.replay');
        Route::post('/requests/{id}/share', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'share'])->name('requests.share');

        // Outgoing HTTP Requests
        Route::get('/outgoing-requests', [Taoshan98\LaravelApiWatcher\Http\Controllers\OutgoingApiController::class, 'index'])->name('outgoing.index');
        Route::get('/outgoing-requests/stats', [Taoshan98\LaravelApiWatcher\Http\Controllers\OutgoingApiController::class, 'stats'])->name('outgoing.stats');
        Route::get('/outgoing-requests/{id}', [Taoshan98\LaravelApiWatcher\Http\Controllers\OutgoingApiController::class, 'show'])->name('outgoing.show');

        Route::get('/keys', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'indexKeys'])->name('keys.index');
        Route::post('/keys', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'storeKey'])->name('keys.store');
        Route::patch('/keys/{id}', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'updateKey'])->name('keys.update');
        Route::post('/keys/{id}/refresh', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'refreshKey'])->name('keys.refresh');
        Route::delete('/keys/{id}', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'destroyKey'])->name('keys.destroy');
    });

    Route::get('/shared/{id}', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'showShared'])->name('shared');

    // Catch-all route for Vue SPA
    Route::get('/{view?}', function () {
        return view('api-watcher::app');
    })->where('view', '(.*)')->name('index');
});
