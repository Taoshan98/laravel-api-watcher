<?php

namespace Taoshan98\LaravelApiWatcher\Tests\Feature;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Taoshan98\LaravelApiWatcher\Models\ApiWatcherKey;
use Taoshan98\LaravelApiWatcher\Services\SensitiveDataRedactor;
use Taoshan98\LaravelApiWatcher\Tests\TestCase;

class SecurityScopesAndRedactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    #[Test]
    public function key_scope_validation_works()
    {
        $token = ApiWatcherKey::createKey('Scoped Key', ['read:stats']);
        $keyModel = ApiWatcherKey::findToken($token);

        expect($keyModel->hasScope('read:stats'))->toBeTrue();
        expect($keyModel->hasScope('manage:keys'))->toBeFalse();
    }

    #[Test]
    public function wild_card_scope_allows_everything()
    {
        $token = ApiWatcherKey::createKey('Wildcard Key', ['*']);
        $keyModel = ApiWatcherKey::findToken($token);

        expect($keyModel->hasScope('read:stats'))->toBeTrue();
        expect($keyModel->hasScope('manage:keys'))->toBeTrue();
    }

    #[Test]
    public function custom_redaction_callback_is_applied()
    {
        Config::set('api-watcher.redaction.enabled', true);
        Config::set('api-watcher.redaction.callback', function (array $data) {
            if (isset($data['ssn'])) {
                $data['ssn'] = 'XXX-XX-XXXX';
            }

            return $data;
        });

        $redactor = new SensitiveDataRedactor;
        $result = $redactor->redactArray([
            'user' => 'John',
            'ssn' => '123-45-6789',
        ]);

        expect($result['ssn'])->toBe('XXX-XX-XXXX');
        expect($result['user'])->toBe('John');
    }
}
