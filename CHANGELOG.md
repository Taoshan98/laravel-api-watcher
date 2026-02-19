# Changelog

All notable changes to `laravel-api-watcher` will be documented in this file.

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
