# Changelog

All notable changes to `laravel-api-watcher` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-07-27

### Added
- **🌐 Outgoing API Observability (Egress Tracking)**: Automatic transparent tracking of third-party HTTP requests sent via Laravel's native `Http::` client (Stripe, OpenAI, Twilio, etc.) with parent-child request correlation.
- **🌐 Dedicated Outgoing Requests Dashboard & API**: Added `/api/outgoing-requests`, `/api/outgoing-requests/stats`, `/api/outgoing-requests/{id}` endpoints.
- **📦 Memory-Efficient Exporters**: Streamed chunking (`chunk(500)`) for JSON/CSV exports (`api-watcher:export`) with flat RAM footprint on large datasets.
- **📬 Postman Collection Exporter**: Added `php artisan api-watcher:export-postman` command generating Postman Collection v2.1 files.
- **📄 SLA & Uptime Report Generator**: Added `php artisan api-watcher:report` command generating SLA Uptime %, P95/P99 latency calculations, and error breakdown reports.
- **🔍 Request Diffing**: Added side-by-side JSON payload & latency comparison tool (`POST /api/requests/diff`).
- **🚨 Schema Drift Detector**: Added structural response JSON type-mapping change detection (`GET /api/schema-drifts`) to detect breaking API changes.
- **🌊 Visual Waterfall Timeline**: Added DB query execution timeline (`DB::listen`) & outgoing HTTP call breakdown (`GET /api/requests/{id}/waterfall`).
- **📈 Latency Trend & Abuse Diagnostics**: Added `TrendAnalyzer` (24h vs 7-day baseline degradation detector) and `AbuseDetector` (suspicious IP 401/429 rate limit breach detector) powering `/api/diagnostics`.
- **🚨 Multichannel Alerting**: Added native Slack Webhook and Generic HTTP Webhook (Teams, Discord, Custom Endpoints) support to `MonitorApiHealth`.
- **🎯 Probabilistic Sampling Engine**: Added customizable sampling rate (`sampling.rate`, `always_sample_errors`, `always_sample_slow_ms`).
- **⚡ Redis List Buffer Driver**: High-throughput storage driver using Redis lists (`rpush`) and artisan worker (`api-watcher:flush`).
- **🔌 Public REST API & Key Scopes**: Database-backed API Key authentication with SHA-256 hashing and granular scope validation (`read:stats`, `read:requests`, `manage:keys`).
- **🛠️ Developer Tools**: Native cURL command generator (`CurlGenerator`), API request replay endpoint, and 24-hour signed shareable links.
- **⚡ Real-Time Live Stream**: Server-Sent Events (SSE) live request tailing endpoint (`/api-watcher/api/live-stream`).

### Security
- **Comprehensive Redaction**: Extended `SensitiveDataRedactor` to redact sensitive fields (`password`, `credit_card`, `token`, etc.) in URL-encoded form POST submissions, JSON strings, and outgoing HTTP request payloads.
- **Fail-Safe Logging**: All logging operations execute inside isolated `try-catch` blocks to prevent logging errors from impacting application responses.
- **AES-256 Encryption**: Optional encryption at rest for request headers and bodies in the database.
- **PHP 8.2+ Attributes**: Updated all PHPUnit annotations to native `#[Test]` attributes across the test suite.

### Performance & Quality
- **PHPStan Level 5 Compliance**: Verified 100% clean static analysis with 0 errors across 38 source files.
- **PSR-12 Code Styling**: Standardized code formatting via Laravel Pint across all 60 project files.
- **Expanded Test Suite**: Built 47 automated Pest tests with 144 assertions passing.

---

## [1.0.0] - 2026-02-19

### Added
- **Dashboard**: Full Vue.js dashboard to monitor API requests in real-time.
- **Request Logging**: Middleware to capture requests, responses, headers, and duration.
- **Storage Drivers**: Support for Database, Redis, and File storage.
- **Analytics**: Visualization of request volume, error rates, status code distribution, and latency.
- **Alerting**: Configurable alerts for error rates and high latency via Mail, Slack, and Discord.
- **Console Commands**:
    - `api-watcher:prune`: Delete old request logs.
    - `api-watcher:export`: Export logs to JSON or CSV.
    - `api-watcher:clear`: Truncate request logs.
    - `api-watcher:monitor`: Check health metrics and trigger alerts.
- **Security**: Sensitive data redaction for headers and body fields.

### Changed
- Initial release.
