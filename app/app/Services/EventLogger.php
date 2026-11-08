<?php

namespace App\Services;

use App\Models\EventLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single entry point for structured application logging.
 *
 * Every call writes to two places:
 *  - the file log channel (storage/logs/app-YYYY-MM-DD.log, JSON lines)
 *  - the `event_logs` table (queryable audit trail, 90-day retention)
 *
 * The two are linked by `request_id` so one incident can be correlated
 * from the browser all the way down to the database row.
 */
class EventLogger
{
    public const LEVELS = ['debug', 'info', 'warning', 'error', 'critical'];

    /**
     * Log a successful business event (level = info).
     *
     * @param  array<string, mixed>  $context
     */
    public static function success(string $event, array $context = [], ?string $requestId = null, ?string $actor = null): void
    {
        static::write('info', $event, 'success', $context, $requestId, $actor);
    }

    /**
     * Log a rejected / anomalous-but-expected business event (level = warning).
     *
     * @param  array<string, mixed>  $context
     */
    public static function rejected(string $event, array $context = [], ?string $requestId = null, ?string $actor = null): void
    {
        static::write('warning', $event, 'failed', $context, $requestId, $actor);
    }

    /**
     * Log a system failure (level = error).
     *
     * @param  array<string, mixed>  $context
     */
    public static function fail(string $event, array $context = [], ?string $requestId = null, ?string $actor = null): void
    {
        static::write('error', $event, 'failed', $context, $requestId, $actor);
    }

    /**
     * Write one event to both the log file and the event_logs table.
     *
     * @param  array<string, mixed>  $context
     */
    public static function write(
        string $level,
        string $event,
        string $status,
        array $context = [],
        ?string $requestId = null,
        ?string $actor = null,
    ): void {
        $level = in_array($level, self::LEVELS, true) ? $level : 'info';

        $requestId ??= static::currentRequestId();

        $message = $context['message'] ?? match ($event) {
            'order.created' => 'Pesanan berhasil dibuat',
            'order.duplicate' => 'Pesanan duplikat dicegah',
            'order.rejected' => 'Pesanan ditolak: '.($context['reason_message'] ?? $context['reason'] ?? 'unknown'),
            'order.db_failed' => 'Gagal menyimpan pesanan ke database',
            'quote.calculated' => 'Ongkir berhasil dihitung',
            'quote.failed' => 'Ongkir gagal dihitung: '.($context['reason'] ?? 'unknown'),
            'quote.provider_error' => 'Provider geocoding gagal',
            'quote.fallback_used' => 'Ongkir menggunakan tarif dasar (fallback)',
            'product.created' => 'Menu ditambahkan',
            'product.updated' => 'Menu diperbarui',
            'product.deleted' => 'Menu dihapus',
            'order.status_changed' => 'Status pesanan diubah',
            'settings.updated' => 'Pengaturan toko diperbarui',
            'admin.login.success' => 'Admin login berhasil',
            'admin.login.failed' => 'Admin login gagal',
            'admin.login.throttled' => 'Admin login terkunci (throttle)',
            'admin.logout' => 'Admin logout',
            'data.exported' => 'Data pesanan diekspor',
            'backup.started' => 'Backup database dimulai',
            'backup.completed' => 'Backup database selesai',
            'backup.failed' => 'Backup database GAGAL',
            'backup.table_failed' => 'Backup gagal untuk satu tabel',
            'backup.purged' => 'Backup lama dihapus',
            'client.error' => 'Error dari browser dilaporkan',
            'http.server_error' => 'Server error pada request',
            default => ucfirst(str_replace(['.', '_'], ' ', $event)),
        };

        $context += [
            'request_id' => $requestId,
            'event' => $event,
            'status' => $status,
        ];

        if (app()->runningInConsole()) {
            // Queue/console context
            $context['ip'] ??= null;
            $context['user_agent'] ??= null;
        } else {
            $req = request();
            $context['ip'] ??= $req->ip();
            $context['user_agent'] ??= substr((string) $req->header('User-Agent'), 0, 300);
            $context['route'] ??= $req->route()?->getName() ?? $req->path();
            $context['method'] ??= $req->method();
        }

        $actor ??= request()->user()?->email;

        Log::channel('app')->log($level, $message, $context);

        // Notifikasi Slack untuk level critical/error jika webhook diisi.
        $slackUrl = config('logging.channels.slack.url');
        if ($slackUrl && in_array($level, ['critical', 'error'], true)) {
            try {
                Log::channel('slack')->{$level}($message, $context);
            } catch (Throwable) {
                // Tidak boleh mengganggu alur utama.
            }
        }

        try {
            EventLog::create([
                'event' => $event,
                'level' => $level,
                'status' => $status,
                'actor_type' => static::actorType($actor, $event),
                'actor_id' => $actor,
                'subject_type' => $context['subject_type'] ?? null,
                'subject_id' => $context['subject_id'] ?? null,
                'message' => $message,
                'context' => json_encode(static::filterContext($context), JSON_THROW_ON_ERROR),
                'request_id' => static::normalizeUuid($requestId),
                'ip_address' => $context['ip'] ?? null,
                'user_agent' => $context['user_agent'] ?? null,
            ]);
        } catch (Throwable) {
            // Audit persistence must never break the request flow.
            // The file log line above already captured the event.
        }
    }

    private static function currentRequestId(): ?string
    {
        // Prefer the id generated by the AssignRequestId middleware (stored as a
        // request attribute), so every event shares the same correlation id even
        // when the client did not send an X-Request-Id header.
        try {
            $request = request();
            $fromAttribute = $request?->attributes->get('request_id');
            if (is_string($fromAttribute) && $fromAttribute !== '') {
                return $fromAttribute;
            }

            return $request?->header('X-Request-Id');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Accept a valid UUID, otherwise store null. The column type is uuid so
     * malformed values cannot round-trip.
     */
    private static function normalizeUuid(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) ? strtolower($value) : null;
    }

    private static function actorType(?string $actor, string $event): string
    {
        if (str_starts_with($event, 'client.')) {
            return 'client';
        }
        if (str_starts_with($event, 'backup.') || str_starts_with($event, 'http.')) {
            return 'system';
        }
        if (str_starts_with($event, 'order.')) {
            return 'customer';
        }

        return $actor !== null ? 'admin' : 'system';
    }

    /**
     * Keep only safe, serialisable context entries for the database.
     * PII (customer name / phone / address / note) is masked or hashed so
     * the audit trail can never be reverse-mapped to a specific customer.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private static function filterContext(array $context): array
    {
        $piiKeys = [
            'customer_name' => 'name_masked',
            'phone' => 'phone_masked',
            'address' => 'address_masked',
            'note' => 'note_hashed',
        ];

        $safe = [];
        foreach ($context as $key => $value) {
            if (in_array($key, ['password', 'password_confirmation', 'current_password', 'authorization'], true)) {
                $safe[$key] = '[redacted]';
                continue;
            }

            if (isset($piiKeys[$key]) && is_string($value) && $value !== '') {
                $safe[$piiKeys[$key]] = match ($piiKeys[$key]) {
                    'phone_masked' => self::maskPhone($value),
                    'name_masked' => self::maskName($value),
                    'address_masked' => self::maskAddress($value),
                    'note_hashed' => substr(hash('sha256', $value), 0, 12),
                };
                continue;
            }

            $safe[$key] = is_scalar($value) || $value === null
                ? $value
                : (is_object($value) ? get_class($value) : gettype($value));
        }

        return $safe;
    }

    /**
     * Keep the last 3 digits only: 6281234567890 -> "****7890"
     */
    private static function maskPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone) ?? '';

        return $phone === '' ? '****' : str_repeat('*', max(0, strlen($phone) - 4)).substr($phone, -4);
    }

    /**
     * Keep first name only: "Budi Santoso" -> "B***"
     */
    private static function maskName(string $name): string
    {
        $first = strtok($name, " \t");

        return $first === false || $first === '' ? '***' : substr($first, 0, 1).str_repeat('*', max(0, strlen($first) - 1));
    }

    /**
     * Drop street / house number, keep postal-code prefix only.
     * "Jl. Kenanga No 10, Bandung" -> "*****"
     */
    private static function maskAddress(string $address): string
    {
        return '*****'.substr(hash('sha256', $address), 0, 4);
    }
}
