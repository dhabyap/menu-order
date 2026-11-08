# LOGGING-ENHANCEMENTS.md — Daftar Enhance / Tambahan Website

> Audit: `LOGGING-AUDIT.md`. Status: **SELESAI** (G01–G14 + matriks §2 ditutup).

## Tier 1 — Core Logging
- `EventLogger` dual-write (file + `event_logs`).
- Channel `app` (daily/JSON/30h) + channel `json`.
- `event_logs` migration + model.

## Tier 2 — Domain Events
- `order.created`, `order.rejected`, `order.db_failed`, `order.duplicate`, `order.status_changed`, `order.lookup`.
- `quote.calculated`, `quote.failed`, `quote.provider_error`, `quote.fallback_used`.
- `product.created/updated/deleted/unavailable`.
- `settings.updated`.
- `backup.started/completed/failed/table_failed/purged`.
- `data.exported`, `store_config.whatsapp_missing`.

## Tier 3 — Auth / Admin Events
- `admin.login.success`, `admin.login.failed`, `admin.login.throttled`, `admin.logout`.
- `admin.action.success`, `admin.action.failed`.
- `admin.backup_downloaded`, `admin.order_viewed`.

## Tier 4 — Client Error Reporting
- `POST /api/client-logs` (throttle 30/1min), `ClientLogController`, `client.error`.
- JS hook `catalog.js` + `admin.js` (`onerror`, `unhandledrejection`, `fetch` patch).

## Tier 5 — Ops & Retention
- `log:purge` daily (event_logs > 90 hari).
- Retensi file 30 hari ; DB 90 hari.
- Notifikasi Slack (critical/error) via `LOG_SLACK_WEBHOOK_URL`.
- Timezone `Asia/Jakarta`; `LOG_LEVEL=info`.

## File Baru
- `app/Services/EventLogger.php`
- `app/Models/EventLog.php`
- `app/database/migrations/2026_11_04_000001_create_event_logs_table.php`
- `app/Http/Middleware/AssignRequestId.php`
- `app/Http/Middleware/LogHttpRequest.php`
- `app/Http/Controllers/Api/ClientLogController.php`
- `app/Console/Commands/PurgeOldLogs.php`
- `app/LOGGING.md`
- `app/LOGGING-IMPLEMENTATION.md`
- `app/tests/Feature/EventLogTest.php`
- `app/tests/Feature/AdminActivityLogTest.php`

## File Diubah
- `config/logging.php`, `config/app.php`, `.env`, `.env.example`, `Exceptions/Handler.php`, `Services/OrderService.php`, `Services/DeliveryQuoteService.php`, controllers, middleware, kernel, routes, models, JS, blade, `LOGGING-AUDIT.md`.

## Sisa Verifikasi
- `php artisan migrate --force`
- `php artisan test`
- Checklist manual §9 (15 poin).

Selesai.