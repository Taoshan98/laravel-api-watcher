<?php

declare(strict_types=1);

namespace Taoshan98\LaravelApiWatcher\Services;

use Illuminate\Support\Str;

class SensitiveDataRedactor
{
    protected array $keysToRedact;

    protected string $replacement;

    protected bool $hashRedacted;

    public function __construct()
    {
        $this->keysToRedact = config('api-watcher.redaction.fields', []);
        $this->replacement = config('api-watcher.redaction.replacement', '[REDACTED]');
        $this->hashRedacted = config('api-watcher.redaction.hash_redacted', false);
    }

    /**
     * Redact sensitive data from an array.
     */
    public function redactArray(array $data): array
    {
        if (! config('api-watcher.redaction.enabled', true)) {
            return $data;
        }

        $customCallback = config('api-watcher.redaction.callback');
        if (is_callable($customCallback)) {
            $data = $customCallback($data);
            if (! is_array($data)) {
                return [];
            }
        }

        foreach ($data as $key => $value) {
            if (is_string($key) && $this->shouldRedact($key)) {
                $data[$key] = $this->redactValue($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->redactArray($value);
            } elseif (is_string($value) && $this->isJson($value)) {
                // Attempt to decode JSON strings, redact, and re-encode
                $json = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $data[$key] = json_encode($this->redactArray($json));
                }
            }
        }

        return $data;
    }

    /**
     * Redact sensitive data from a string (JSON or query string).
     */
    public function redactString(string $content): string
    {
        if (! config('api-watcher.redaction.enabled', true) || empty($content)) {
            return $content;
        }

        if ($this->isJson($content)) {
            $json = json_decode($content, true);
            if (is_array($json)) {
                return (string) json_encode($this->redactArray($json));
            }
        }

        // Handle URL encoded form string
        if (str_contains($content, '=')) {
            parse_str($content, $parsed);
            if (! empty($parsed) && is_array($parsed)) {
                $redacted = $this->redactArray($parsed);

                return http_build_query($redacted);
            }
        }

        return $content;
    }

    protected function shouldRedact(string $key): bool
    {
        $lowerKey = Str::lower($key);
        foreach ($this->keysToRedact as $pattern) {
            if (Str::is(Str::lower($pattern), $lowerKey)) {
                return true;
            }
        }

        return false;
    }

    protected function redactValue($value): string
    {
        if ($this->hashRedacted && is_string($value)) {
            return $this->replacement.' (SHA256: '.hash('sha256', $value).')';
        }

        return $this->replacement;
    }

    protected function isJson($string): bool
    {
        if (! is_string($string)) {
            return false;
        }
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
