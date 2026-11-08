<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Attach an `X-Request-Id` header to every request so a single incident can
 * be traced from the browser console to the log file to the database row.
 *
 * The id is generated server-side; a client-supplied value is accepted only
 * when it already looks like a valid UUID, so we never log attacker data.
 */
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->header('X-Request-Id');

        $requestId = (is_string($incoming) && preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $incoming
        )) ? strtolower($incoming) : (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
