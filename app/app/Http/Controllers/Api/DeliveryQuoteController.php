<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryQuoteRequest;
use App\Services\DeliveryQuoteService;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

class DeliveryQuoteController extends Controller
{
    public function __invoke(DeliveryQuoteRequest $request, DeliveryQuoteService $quotes): JsonResponse
    {
        try {
            return response()->json($quotes->quote($request->validated('address')));
        } catch (RuntimeException $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        } catch (Throwable $error) {
            report($error);
            return response()->json(['message' => 'Estimasi ongkir sementara gagal. Coba lagi nanti.'], 503);
        }
    }
}
