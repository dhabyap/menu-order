# Logging — implementasi Fase 0-4

## Summary

This document records what changed for the logging audit in `app/LOGGING-AUDIT.md`.
It is not a design document; changes are audited by re-reading the audit file.

Fased implemented: **0 (core), 1, 2, 3, 4**.

---

## Fase 0 — pesanan + admin login + backup logging

### New files
- `app/Services/EventLogger.php` — single entry point for all application events:
  - `success(event, context)` → level `info`, status `success`
  - `rejected(event, context)` → level `warning`, status `failed`
  - `fail(event, context)` → level `error`, status `failed`
  - Writes to both the structured `app` log channel and the `event_logs` table, linked by `request_id`.
  - PII masked/hashed before writing to the table: phone last 4 digits, name first initial, address dropped, note hashed.

- `app/Models/EventLog.php` — read-once audit model for `event_logs`.

- `app/database/migrations/2026_11_04_000001_create_event_logs_table.php`

### Edited
- `config/logging.php`:
  - New `'app'` channel (`daily`, `days: 30`, JSON via Monolog StreamHandler) merged into the default `stack` via `LOG_STACK_CHANNELS=app`.
  - Kept `single` for emergencies only.
- `.env` / `.env.example`: `LOG_LEVEL=info` (production); added `LOG_STACK_CHANNELS`.
- `config/app.php`: timezone `Asia/Jakarta` instead of `UTC`.
- `app/Exceptions/Handler.php`:
  - Every exception now writes to `Log::channel('app')` with request context (route, ip, method, user agent).
  - 4xx responses recorded as `http.client_error`.
  - 429 on `admin/login` recorded as `admin.login.throttled`.
- `app/Services/OrderService.php`:
  - `order.created`, `order.duplicate`, `order.db_failed` logs.
  - `order.rejected` logged at each throw site (address, product unavailable, WA missing, estimate, option missing/invalid, store closed).
- `app/Http/Controllers/Api/CheckoutController.php`: comment clarifies `RuntimeException` is already logged in OrderService; no duplicate logic.
- `app/Http/Controllers/Admin/AuthController.php`: `admin.login.success` / `admin.login.failed` / `admin.logout` event logging.
- `app/Console/Commands/BackupDatabase.php`: `backup.started`, `backup.completed`, `backup.failed`, `backup.table_failed`, `backup.purged` event logging inside the command; exit code of `Artisan::call()` is checked in the route handler.
- `routes/web.php`: backup route returns 500 + `backup.failed` when artisan exit code != 0.

### Verified by test
- `tests/Feature/EventLogTest.php` validates backups, failed login, failed validation action, and throttled login.

---

## Fase 1 — request id + HTTP logging middleware

### New files
- `app/Http/Middleware/AssignRequestId.php`:
  - Accepts a client-supplied UUID only if it matches the pattern; otherwise generates server-side `Str::uuid()`.
  - Stored as a request attribute; response header `X-Request-Id` is set.
- `app/Http/Middleware/LogHttpRequest.php`:
  - One structured line per HTTP request at the end of the call chain.
  - 2xx → `debug`/`http.request`; 4xx → `warning`/`http.client_error`; 5xx → `critical`/`http.server_error`.
  - Static assets (css, js, png, …) excluded.
- Registered both in `Http/Kernel.php` `$middleware`.

---

## Fase 2 — sensitive GET audits + admin read logging

### `app/Http/Middleware/LogAdminActivity.php`
- Replaced the `GET` exclusion list with an explicit `AUDITED_GET_EVENTS` map:
  - `admin.orders.export` → `data.exported`
  - `admin.backups.download` → `admin.backup_downloaded`
  - `admin.orders.show` → `admin.order_viewed`
- POST/PUT/PATCH/DELETE actions: 302 validation errors still written as `admin.action.failed`; mutations are written only when the response status is `< 400`.
- `ActivityLog` (`user_email`, `action`, `description`, `ip_address`, `user_agent`) is written alongside `EventLogger::success('admin.action.success', ...)` so existing UI continues to work.
- `ActivityLogController::systemLog` / route `/admin/system-log` filter the `event_logs` table by `event`, `level`, `status`.

### Tests
- `tests/Feature/AdminActivityLogTest.php`: success path writes both tables; failed validation writes no success row but a `admin.action.failed` row; GET requests never logged.

---

## Fase 3 — client error endpoint + JS hook

### Controller / route
- `app/Http/Controllers/Api/ClientLogController.php`: ingests `{message, stack, url, line, col, source, request_id}` into `event_logs` under `client.error`. Returns 202.
- `routes/api.php`: `POST /api/client-logs` with `throttle:30,1`.

### Frontend
- `public/assets/catalog/catalog.js`: `initClientErrorReporting()` registers `window.onerror`, `unhandledrejection` and patches `fetch` to remember the `X-Request-Id` from response headers so client errors can be correlated to server-side log lines.
- `public/assets/admin/admin.js`: identical reporting, tagged `source: 'admin'`.

---

## Fase 4 — retention scheduler

- `app/Console/Commands/PurgeOldLogs.php`: deletes `event_logs` rows older than N days (default 90).
- Registered automatically via Kernel.php `load(__DIR__.'/Commands');` and scheduled daily via `Kernel::schedule()`.

---

## Edge cases observed & fixed along the way

| Issue | Fix |
|---|---|
| Backup "succeeded" silently | Route now checks `Artisan::call()` return value; non-zero → `backup.failed` + HTTP 500 |
| `activity_logs` written for failures | Middleware records failure separately |
| PII unmasked in audit trail | `EventLogger::filterContext` masks name/phone/address/note |
| Request ID always missing | `AssignRequestId` middleware + response header |
| Static-asset noise in HTTP log | extension blacklist in `LogHttpRequest` |
| `scopeLatest($query, $limit = 50)` conflicts with Eloquent's `latest()` | removed scope; explicit `latest('created_at')->limit(50)` |
| `store-config` returning empty WhatsApp silently | logged `store_config.whatsapp_missing` warning |

---

## Verification checklist (hand-run after migrate)

Run in PowerShell from this checkout:

```powershell
php artisan migrate --force
php artisan test
php artisan vendor:publish --force -l publish-logs
```

Then manually verify:
1. POST a normal checkout → two `event_logs` rows: `order.created` (info) + `http.request` (debug) linked by identical `request_id`.
2. POST checkout without WhatsApp → one `order.rejected` (warning) + no `order.created`.
3. 6 wrong login attempts → final attempt shows `admin.login.throttled` in `event_logs`.
4. POST `/admin/settings` empty → `admin.action.failed` in both tables, zero `admin.action.success`.
5. POST `/admin/backups/now` with write-protected backup folder → HTTP 500 + `backup.failed` row.
6. Open browser dev tools, throw in console → 5-second window sees `client.error` in `event_logs` matching the request id from headers.
7. `GET /admin/system-log?level=critical&status=failed` returns filtered events.
