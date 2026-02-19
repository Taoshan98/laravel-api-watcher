<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('api-watcher.dashboard.path', 'api-watcher') . '/api/v1',
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
        Route::get('/analytics', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'analytics'])->name('analytics');
        Route::get('/config', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'config'])->name('config');
        Route::post('/actions/prune', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'prune'])->name('actions.prune');
        Route::post('/actions/clear', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'clear'])->name('actions.clear');
        Route::post('/actions/test-alert', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'testAlert'])->name('actions.test-alert');
        Route::get('/requests', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'index'])->name('requests.index');
        Route::get('/requests/{id}', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'show'])->name('requests.show');
        
        Route::get('/keys', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'indexKeys'])->name('keys.index');
        Route::post('/keys', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'storeKey'])->name('keys.store');
        Route::patch('/keys/{id}', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'updateKey'])->name('keys.update');
        Route::post('/keys/{id}/refresh', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'refreshKey'])->name('keys.refresh');
        Route::delete('/keys/{id}', [Taoshan98\LaravelApiWatcher\Http\Controllers\ApiWatcherController::class, 'destroyKey'])->name('keys.destroy');
    });

    // Catch-all route for Vue SPA
    Route::get('/{view?}', function () {
        return view('api-watcher::app');
    })->where('view', '(.*)')->name('index');
});
