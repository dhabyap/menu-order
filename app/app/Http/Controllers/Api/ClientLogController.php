<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EventLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ingest uncaught JavaScript errors from the customer catalog and admin
 * dashboard so client-side failures become observable on the server.
 */
class ClientLogController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'stack' => 'nullable|string|max:4000',
            'url' => 'nullable|string|max:1000',
            'line' => 'nullable|integer',
            'col' => 'nullable|integer',
            'source' => 'nullable|string|max:60',
            'request_id' => 'nullable|string|max:64',
        ]);

        $source = $validated['source'] ?? 'catalog';
        $level = str_starts_with($source, 'admin') ? 'error' : 'warning';

        EventLogger::write(
            $level,
            'client.error',
            'failed',
            [
                'message' => $validated['message'],
                'url' => $validated['url'] ?? null,
                'line' => $validated['line'] ?? null,
                'col' => $validated['col'] ?? null,
                'source' => $source,
                'stack_preview' => isset($validated['stack']) ? substr($validated['stack'], 0, 500) : null,
            ],
            $validated['request_id'] ?? null
        );

        return response()->json(['logged' => true], 202);
    }
}
