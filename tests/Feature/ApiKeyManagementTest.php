<?php

namespace Taoshan98\LaravelApiWatcher\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey;
use Taoshan98\LaravelApiWatcher\Tests\TestCase;

class ApiKeyManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure migration is run
        $this->artisan('migrate');
    }

    /** @test */
    public function can_create_api_key_via_command()
    {
        $this->artisan('api-watcher:create-key', ['name' => 'Test Key'])
             ->assertExitCode(0);

        $this->assertDatabaseHas('api_watcher_keys', ['name' => 'Test Key']);
    }

    /** @test */
    public function can_authenticate_with_database_key()
    {
        Config::set('api-watcher.api.enabled', true);

        // Create a key manually to get the plain token
        $token = ApiWatcherKey::createKey('Test Client');
        
        $response = $this->getJson('/api-watcher/api/v1/stats', [
            'X-API-WATCHER-KEY' => $token
        ]);

        $response->assertStatus(200);
        
        // Verify last_used_at updated
        $key = ApiWatcherKey::where('name', 'Test Client')->first();
        $this->assertNotNull($key->last_used_at);
    }

    /** @test */
    public function cannot_authenticate_with_invalid_database_key()
    {
        Config::set('api-watcher.api.enabled', true);

        $response = $this->getJson('/api-watcher/api/v1/stats', [
            'X-API-WATCHER-KEY' => '1|invalidsecret'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function console_command_outputs_token()
    {
        $this->artisan('api-watcher:create-key', ['name' => 'CLI Key'])
             ->expectsOutputToContain('|') // Should contain the separator
             ->assertExitCode(0);
    }

    /** @test */
    public function can_regenerate_api_key()
    {
        $token = ApiWatcherKey::createKey('Regen Key');
        $originalKey = ApiWatcherKey::where('name', 'Regen Key')->first();
        $originalHash = $originalKey->token;

        $newToken = $originalKey->regenerate();
        
        $this->assertNotEquals($token, $newToken);
        $this->assertNotEquals($originalHash, $originalKey->fresh()->token);
        
        // Old token should fail
        $this->assertNull(ApiWatcherKey::findToken($token));
        
        // New token should work
        $this->assertNotNull(ApiWatcherKey::findToken($newToken));
    }
}
