<?php

namespace Taoshan98\LaravelApiWatcher\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Taoshan98\LaravelApiWatcher\Models\ApiRequest;
use Taoshan98\LaravelApiWatcher\Tests\TestCase;

class PublicApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Define route naming for tests within the package context if needed
        // Assuming the package routes are loaded automatically by the ServiceProvider in TestCase
    }

    /** @test */
    public function secure_api_is_disabled_by_default()
    {
        // Ensure API is disabled in config
        Config::set('api-watcher.api.enabled', false);
        
        $response = $this->getJson('/api-watcher/api/v1/stats');
        $response->assertStatus(403);
    }

    /** @test */
    public function it_rejects_request_without_api_key()
    {
        Config::set('api-watcher.api.enabled', true);

        $response = $this->getJson('/api-watcher/api/v1/stats');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_rejects_request_with_invalid_api_key()
    {
        Config::set('api-watcher.api.enabled', true);

        $response = $this->getJson('/api-watcher/api/v1/stats', [
            'X-API-WATCHER-KEY' => 'wrong-key'
        ]);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_allows_request_with_valid_api_key()
    {
        Config::set('api-watcher.api.enabled', true);
        
        // Create a valid key
        $token = \Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey::createKey('Test Key');

        $response = $this->getJson('/api-watcher/api/v1/stats', [
            'X-API-WATCHER-KEY' => $token
        ]);
        $response->assertStatus(200);
    }

    /** @test */
    public function it_returns_list_of_requests()
    {
        Config::set('api-watcher.api.enabled', true);
        
        $token = \Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey::createKey('Test Key');
        
        ApiRequest::factory()->count(3)->create();

        $response = $this->getJson('/api-watcher/api/v1/requests', [
            'X-API-WATCHER-KEY' => $token
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_returns_single_request()
    {
        Config::set('api-watcher.api.enabled', true);
        
        $token = \Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey::createKey('Test Key');
        
        $request = ApiRequest::factory()->create();

        $response = $this->getJson("/api-watcher/api/v1/requests/{$request->id}", [
            'X-API-WATCHER-KEY' => $token
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $request->id);
    }
}
