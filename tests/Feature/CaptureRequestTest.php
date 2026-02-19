<?php

use Taoshan98\LaravelApiWatcher\Models\ApiRequest;
use Illuminate\Support\Facades\Route;
use Taoshan98\LaravelApiWatcher\Http\Middleware\CaptureApiRequest;

// uses(Taoshan98\LaravelApiWatcher\Tests\TestCase::class);

beforeEach(function () {
    config()->set('api-watcher.enabled', true);
    config()->set('api-watcher.capture.async', false); // Sync for testing
    
    Route::middleware(CaptureApiRequest::class)->group(function () {
        Route::get('api/test', function () {
            return response()->json(['message' => 'success']);
        });
        
        Route::post('api/test-post', function () {
            return response()->json(['message' => 'created'], 201);
        });
    });
});

it('captures api requests', function () {
    $this->get('api/test')
        ->assertOk();

    // dump(config('api-watcher.storage.driver'));
    // dump(app(\Taoshan98\LaravelApiWatcher\Contracts\ApiWatcherStorageDriver::class));
    // dump(Schema::hasTable('api_watcher_requests'));

    expect(ApiRequest::count())->toBe(1);
    
    $request = ApiRequest::first();
    expect($request->method)->toBe('GET')
        ->and($request->url)->toContain('api/test')
        ->and($request->status_code)->toBe(200);
});

it('captures request body', function () {
    $this->postJson('api/test-post', ['name' => 'test'])
        ->assertCreated();

    expect(ApiRequest::count())->toBe(1);
    
    $request = ApiRequest::first();
    expect($request->method)->toBe('POST')
        ->and($request->request_body)->toContain('test');
});

it('respects ignore patterns', function () {
    config()->set('api-watcher.capture.ignore', ['api/ignored']);
    
    Route::middleware(CaptureApiRequest::class)->get('api/ignored', function () {
        return 'ignored';
    });

    $this->get('api/ignored')->assertOk();

    expect(ApiRequest::count())->toBe(0);
});
