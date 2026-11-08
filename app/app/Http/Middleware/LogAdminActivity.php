<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Services\EventLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActivity
{
    private const ACTION_DESCRIPTIONS = [
        'admin.products.create' => 'Menambahkan menu baru',
        'admin.products.update' => 'Mengubah menu',
        'admin.products.toggle' => 'Mengubah status ketersediaan menu',
        'admin.products.delete' => 'Menghapus menu',
        'admin.products.reorder' => 'Mengurutkan ulang menu',
        'admin.orders.update' => 'Mengubah status pesanan',
        'admin.settings.update' => 'Mengubah pengaturan toko',
        'admin.backups.now' => 'Membuat backup database',
    ];

    /**
     * Sensitive read actions that touch customer data — audited even though
     * they are GETs.
     */
    private const AUDITED_GET_EVENTS = [
        'admin.orders.export' => 'data.exported',
        'admin.backups.download' => 'admin.backup_downloaded',
        'admin.orders.show' => 'admin.order_viewed',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $routeName = $request->route()?->getName();

        if (! $routeName || ! str_starts_with($routeName, 'admin.')) {
            return $response;
        }

        // Sensitive GET actions are always audited (they expose customer data).
        if (isset(self::AUDITED_GET_EVENTS[$routeName])) {
            EventLogger::success(self::AUDITED_GET_EVENTS[$routeName], [
                'route' => $routeName,
                'description' => $this->buildDescription($request, $routeName),
            ]);
            return $response;
        }

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        $description = $this->buildDescription($request, $routeName);

        // 4xx/5xx means the mutation did not actually happen — do not write a
        // success row into the audit table; still record the attempt so failed
        // mutations are discoverable.
        if ($response->getStatusCode() < 400) {
            ActivityLog::create([
                'user_email' => $request->user()?->email,
                'action' => $routeName,
                'description' => $description,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            EventLogger::success('admin.action.success', [
                'route' => $routeName,
                'description' => $description,
            ]);
        } else {
            EventLogger::rejected('admin.action.failed', [
                'route' => $routeName,
                'description' => $description,
                'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }

    private function buildDescription(Request $request, string $routeName): string
    {
        $base = self::ACTION_DESCRIPTIONS[$routeName] ?? (self::AUDITED_GET_EVENTS[$routeName] ?? 'Aksi admin: '.$routeName);

        switch ($routeName) {
            case 'admin.products.create':
                $name = $request->input('name');
                return $name ? "Menambahkan menu: {$name}" : $base;
            case 'admin.products.update':
            case 'admin.products.toggle':
            case 'admin.products.delete':
                $id = $request->route('id');
                $name = $request->input('name') ?? ($request->route('name') ?? 'ID '.$id);
                return "{$base} ({$name})";
            case 'admin.orders.update':
                $id = $request->route('id');
                $status = $request->input('status');
                return $base.": pesanan #{$id} → {$status}";
            case 'admin.settings.update':
                $name = $request->input('store_name');
                return $name ? "Mengubah pengaturan toko: {$name}" : 'Mengubah pengaturan toko';
            case 'admin.orders.export':
                return 'Mengunduh daftar pesanan (CSV)';
            case 'admin.backups.download':
                return 'Mengunduh backup: '.$request->route('filename');
            case 'admin.orders.show':
                return 'Melihat detail pesanan #'.$request->route('id');
            default:
                return $base;
        }
    }
}
