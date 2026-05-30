<?php

namespace Commero\Domain\Catalog\Domain\Contracts;

use Commero\Models\Product;

interface ProductRepositoryInterface
{
    public function save(Product $product): Product;

    public function findById(int $id): ?Product;
}
