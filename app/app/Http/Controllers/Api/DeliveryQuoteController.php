<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryQuoteRequest;
use App\Services\DeliveryQuoteService;
use App\Services\EventLogger;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

class DeliveryQuoteController extends Controller
{
    public function __invoke(DeliveryQuoteRequest $request, DeliveryQuoteService $quotes): JsonResponse
    {
        try {
            $address = $request->validated('address');
            $selectedLat = $request->input('selected_lat');
            $selectedLon = $request->input('selected_lon');
            $fallback = (bool) $request->boolean('fallback');

            $result = null;
            if ($selectedLat !== null && $selectedLon !== null) {
                $result = $quotes->quoteForCoordinates(
                    $address,
                    (float) $selectedLat,
                    (float) $selectedLon
                );
            } else {
                $result = $quotes->quote($address, $fallback);
            }

            EventLogger::success('quote.calculated', [
                'fee' => $result['fee'] ?? 0,
                'distance_m' => $result['distance_meters'] ?? null,
                'source' => $result['quote_source'] ?? null,
                'is_estimate' => (bool) ($result['is_estimate'] ?? false),
            ]);

            return response()->json($result);
        } catch (RuntimeException $error) {
            EventLogger::rejected('quote.failed', [
                'reason' => $error->getMessage(),
            ]);

            return response()->json(['message' => $error->getMessage()], 422);
        } catch (Throwable $error) {
            EventLogger::fail('quote.failed', [
                'reason' => $error->getMessage(),
                'exception_class' => get_class($error),
            ]);

            report($error);

            return response()->json(['message' => 'Estimasi ongkir sementara gagal. Coba lagi nanti.'], 503);
        }
    }
}
