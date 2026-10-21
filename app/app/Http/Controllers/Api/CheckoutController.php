<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Illuminate\Support\Facades\Response;
use RuntimeException;

/**
 * Handles the public checkout endpoint.
 */
class CheckoutController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    /**
     * Create a customer order from validated checkout data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreOrderRequest $request)
    {
        try {
            $result = $this->orders->create($request->validated());
        } catch (RuntimeException $e) {
            return Response::json(['message' => $e->getMessage()], 422);
        }

        return Response::json($result, 201);
    }
}
