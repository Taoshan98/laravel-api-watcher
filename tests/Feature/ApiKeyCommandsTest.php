<?php

namespace Taoshan98\LaravelApiWatcher\Tests\Feature;

use Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey;
use Taoshan98\LaravelApiWatcher\Tests\TestCase;

class ApiKeyCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function can_list_keys()
    {
        ApiWatcherKey::createKey('Key 1');
        ApiWatcherKey::createKey('Key 2');

        $this->artisan('api-watcher:list-keys')
             ->expectsOutputToContain('Key 1')
             ->expectsOutputToContain('Key 2')
             ->assertExitCode(0);
    }

    /** @test */
    public function can_rename_key()
    {
        $key = ApiWatcherKey::create(['name' => 'Old Name', 'token' => 'hash']);

        $this->artisan('api-watcher:rename-key', ['id' => $key->id, 'name' => 'New Name'])
             ->expectsOutput("Key renamed to 'New Name' successfully.")
             ->assertExitCode(0);

        $this->assertDatabaseHas('api_watcher_keys', ['id' => $key->id, 'name' => 'New Name']);
    }

    /** @test */
    public function can_regenerate_key()
    {
        $token = ApiWatcherKey::createKey('Regen Key');
        $key = ApiWatcherKey::where('name', 'Regen Key')->first();
        $oldHash = $key->token;

        $this->artisan('api-watcher:regenerate-key', ['id' => $key->id])
             ->expectsConfirmation("Are you sure you want to regenerate the key for 'Regen Key'? The old key will stop working immediately.", 'yes')
             ->expectsOutput('Key regenerated successfully.')
             ->assertExitCode(0);

        $this->assertNotEquals($oldHash, $key->fresh()->token);
    }

    /** @test */
    public function can_delete_key()
    {
        $key = ApiWatcherKey::create(['name' => 'Delete Me', 'token' => 'hash']);

        $this->artisan('api-watcher:delete-key', ['id' => $key->id])
             ->expectsConfirmation("Are you sure you want to delete the key 'Delete Me'? This action cannot be undone.", 'yes')
             ->expectsOutput('Key deleted successfully.')
             ->assertExitCode(0);

        $this->assertDatabaseMissing('api_watcher_keys', ['id' => $key->id]);
    }
}
