<?php

namespace Commero\Http\Controllers;

use Commero\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->cartService->getCart(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $cart = $this->cartService->add(
            (int) $validated['product_id'],
            isset($validated['variant_id']) ? (int) $validated['variant_id'] : null,
            (int) ($validated['quantity'] ?? 1),
        );

        return response()->json([
            'success' => true,
            'data' => $cart,
        ]);
    }

    public function update(Request $request, string $lineId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer'],
        ]);

        $cart = $this->cartService->update($lineId, (int) $validated['quantity']);

        return response()->json([
            'success' => true,
            'data' => $cart,
        ]);
    }

    public function destroy(string $lineId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->cartService->remove($lineId),
        ]);
    }

    public function clear(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->cartService->clear(),
        ]);
    }
}
