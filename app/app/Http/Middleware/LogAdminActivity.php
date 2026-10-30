<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActivity
{
    /**
     * Routes that should NOT be logged (GET dashboard/stats).
     */
    private const EXCLUDED_METHODS = ['GET'];

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

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $routeName = $request->route()?->getName();

        if (! $routeName || ! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        if (! str_starts_with($routeName, 'admin.')) {
            return $response;
        }

        $description = $this->buildDescription($request, $routeName);

        ActivityLog::create([
            'user_email' => $request->user()?->email,
            'action' => $routeName,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $response;
    }

    private function buildDescription(Request $request, string $routeName): string
    {
        $base = self::ACTION_DESCRIPTIONS[$routeName] ?? 'Aksi admin: '.$routeName;

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
            default:
                return $base;
        }
    }
}
