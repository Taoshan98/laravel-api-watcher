<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable/Disable API Watcher
    |--------------------------------------------------------------------------
    |
    | Global switch to enable or disable the package.
    |
    */
    'enabled' => env('API_WATCHER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the UI dashboard.
    |
    */
    'dashboard' => [
        'enabled' => env('API_WATCHER_DASHBOARD_ENABLED', true),
        'path' => 'api-watcher',
        'middleware' => ['web', 'auth', 'can:viewApiWatcher'],
        'ip_allowlist' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Public API Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the external API to retrieve logs programmatically.
    | Protected by Database-backed API Keys (managed via Dashboard/Artisan).
    |
    */
    'api' => [
        'enabled' => env('API_WATCHER_API_ENABLED', false),
        'header' => 'X-API-WATCHER-KEY',
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Capture Settings
    |--------------------------------------------------------------------------
    |
    | define which requests to capture and how.
    |
    */
    'capture' => [
        'match' => ['api/*'],
        'ignore' => [],
        'ignore_methods' => [],
        'capture_request_body' => true,
        'capture_response_body' => true,
        'max_response_body_size_kb' => 64,
        'async' => true,            // dispatch()->afterResponse()
        'queue_connection' => env('API_WATCHER_QUEUE', 'sync'),
        'auto_inject_middleware' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "database", "redis", "file", "null"
    |
    */
    'storage' => [
        'driver' => env('API_WATCHER_DRIVER', 'database'),
        'connection' => env('API_WATCHER_DB_CONNECTION', null),
        'table' => 'api_watcher_requests',
        'retention_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Data Redaction
    |--------------------------------------------------------------------------
    |
    | Keys to redact from request/response bodies and headers.
    |
    */
    'redaction' => [
        'enabled' => true,
        'fields' => ['password', 'token', 'secret', 'authorization', 'api_key', 'credit_card', 'cvv', 'password_confirmation'],
        'patterns' => [],
        'hash_redacted' => false,
        'replacement' => '[REDACTED]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tagging
    |--------------------------------------------------------------------------
    |
    | Automatic tagging of requests.
    |
    */
    'tagging' => [
        'resolvers' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption
    |--------------------------------------------------------------------------
    |
    | Encrypt sensitive data in the database (Headers, Bodies).
    | Note: Enabling this will increase CPU usage and storage size.
    |
    */
    'encryption' => [
        'enabled' => env('API_WATCHER_ENCRYPTION_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerts & Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure thresholds and notifications for API health monitoring.
    | Run `php artisan api-watcher:monitor` to check.
    |
    */
    'alerts' => [
        'enabled' => env('API_WATCHER_ALERTS_ENABLED', false),
        
        // Look back X minutes to calculate metrics
        'check_interval_minutes' => 5, 
        
        // Thresholds to trigger an alert
        'thresholds' => [
            'error_rate' => 5.0, // Percentage of 5xx errors
            'high_latency_ms' => 1000, // Average latency exceeding this
        ],

        // Notification channels
        // You can use standard Laravel channels like 'mail', 'database', etc.
        // Or custom notification classes.
        'channels' => ['mail'],

        'notifications' => [
            'mail' => [
                'to' => env('API_WATCHER_ALERT_MAIL_TO', 'admin@example.com'),
            ],
        ],
    ],
];
