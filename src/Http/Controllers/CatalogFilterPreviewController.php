<?php

namespace Commero\Http\Controllers;

use Commero\Application\Catalog\Queries\CatalogProductListQuery;
use Commero\Support\Locales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogFilterPreviewController extends Controller
{
    public function __invoke(Request $request, CatalogProductListQuery $products, ?string $locale = null): JsonResponse
    {
        $resolvedLocale = Locales::resolve($locale);
        $filters = $request->input('filters', []);
        $categoryScope = collect((array) $request->input('category_scope', []))
            ->filter(fn ($categoryId) => is_numeric($categoryId))
            ->map(fn ($categoryId) => (int) $categoryId)
            ->unique()
            ->values()
            ->all();

        if ($categoryScope !== [] && is_array($filters)) {
            $selectedCategories = collect((array) data_get($filters, 'categories', []))
                ->filter(fn ($categoryId) => is_numeric($categoryId))
                ->map(fn ($categoryId) => (int) $categoryId)
                ->intersect($categoryScope)
                ->values()
                ->all();

            $filters['categories'] = $selectedCategories !== [] ? $selectedCategories : $categoryScope;
        }

        return response()->json([
            'count' => $products->count($resolvedLocale, is_array($filters) ? $filters : []),
        ]);
    }
}
