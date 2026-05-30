<?php

namespace Commero\Application\Catalog\Queries;

use Commero\Application\Catalog\DTOs\CatalogProductCardData;

class CatalogProductCardQuery
{
    public function __construct(private readonly CatalogProductListQuery $productListQuery) {}

    public function handle(string $locale, string $slug): ?CatalogProductCardData
    {
        return $this->productListQuery
            ->handle($locale)
            ->first(fn (CatalogProductCardData $product) => $product->slug === $slug);
    }
}
