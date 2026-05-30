<?php

namespace Commero\Http\Controllers;

use Commero\Application\Catalog\Queries\CatalogProductListQuery;
use Commero\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request, CatalogProductListQuery $productsQuery): View
    {
        $locale = app()->getLocale();
        $searchQuery = $request->input('q', '');
        $page = $request->input('page', 1);
        $perPage = 12;

        // Prepare search filters
        $filters = [];

        // If there's a search query, add it to filters
        if ($searchQuery) {
            $filters['search'] = $searchQuery;
        }

        // Get products with search filter
        $products = $productsQuery->handle($locale, 'popular_desc', $filters, $perPage);

        return view('shophats::pages.search', [
            'products' => $products,
            'searchQuery' => $searchQuery,
            'locale' => $locale,
            'title' => $searchQuery
                ? __('Found products for query ":query"', ['query' => $searchQuery])
                : __('Product search'),
            'meta_description' => __('Результати пошуку товарів у каталозі ShopHats'),
        ]);
    }
}
