<?php

namespace App\Http\Middleware;

use App\Services\EventLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Record one structured line per HTTP request at the end of the call chain.
 * 2xx → info, 4xx → warning, 5xx → error (critical). Static assets are
 * excluded to keep the log signal high.
 */
class LogHttpRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);

        $extension = strtolower(pathinfo(parse_url($request->getRequestUri())['path'] ?? '', PATHINFO_EXTENSION));
        if (in_array($extension, ['css', 'js', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf'], true)) {
            return $response;
        }

        $status = $response->getStatusCode();
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $context = [
            'status' => $status,
            'method' => $request->method(),
            'path' => $request->getRequestUri(),
            'route' => $request->route()?->getName(),
            'duration_ms' => $durationMs,
        ];

        if ($status >= 500) {
            EventLogger::write('critical', 'http.server_error', 'failed', $context, $request->attributes->get('request_id'));
        } elseif ($status >= 400) {
            EventLogger::write('warning', 'http.client_error', 'failed', $context, $request->attributes->get('request_id'));
        } else {
            EventLogger::write('debug', 'http.request', 'success', $context, $request->attributes->get('request_id'));
        }

        return $response;
    }
}
