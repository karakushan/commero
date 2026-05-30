<?php

namespace Commero\Application\Catalog\DTOs;

class CatalogProductCardData
{
    public function __construct(
        public readonly int $id,
        public readonly string $locale,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly string $sku,
        public readonly ?string $brand,
        public readonly ?string $primaryImageUrl,
        public readonly ?string $primaryImageAlt,
        public readonly float $price = 0,
        public readonly ?float $old_price = null,
        public readonly bool $is_on_sale = false,
        public readonly bool $is_hit_sales = false,
        public readonly bool $is_new = false,
        public readonly array $gallery = [],
        public readonly string $stock_status = 'in_stock',
        public readonly string $url = '#',
        public readonly array $categories = [],
        public readonly array $attributes = [],
    ) {}
}
