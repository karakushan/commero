<?php

namespace Commero\Http\Controllers;

use Commero\Exceptions\NovaPoshtaException;
use Commero\Services\NovaPoshtaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutDeliveryController extends Controller
{
    public function cities(Request $request, NovaPoshtaService $novaPoshtaService): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        try {
            return response()->json([
                'data' => $novaPoshtaService->searchSettlements($validated['query']),
            ]);
        } catch (NovaPoshtaException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'data' => [],
            ], 422);
        }
    }

    public function warehouses(Request $request, NovaPoshtaService $novaPoshtaService): JsonResponse
    {
        $validated = $request->validate([
            'city_ref' => ['required', 'string', 'max:255'],
        ]);

        try {
            return response()->json([
                'data' => $novaPoshtaService->getWarehouses($validated['city_ref']),
            ]);
        } catch (NovaPoshtaException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'data' => [],
            ], 422);
        }
    }
}
