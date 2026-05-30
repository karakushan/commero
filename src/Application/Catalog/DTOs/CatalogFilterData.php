<?php

namespace Commero\Application\Catalog\DTOs;

class CatalogFilterData
{
    public function __construct(
        public readonly array $categories,
        public readonly array $attributes,
    ) {}
}
