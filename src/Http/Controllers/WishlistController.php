<?php

namespace Commero\Http\Controllers;

use Commero\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlistService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->wishlistService->getWishlist(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->wishlistService->add((int) $validated['product_id']),
        ]);
    }

    public function destroy(int $productId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->wishlistService->remove($productId),
        ]);
    }

    public function toggle(int $productId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->wishlistService->toggle($productId),
        ]);
    }

    public function clear(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->wishlistService->clear(),
        ]);
    }
}
