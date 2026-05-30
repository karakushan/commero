<?php

namespace Commero\Domain\Catalog\Infrastructure\Repositories;

use Commero\Domain\Catalog\Domain\Contracts\ProductRepositoryInterface;
use Commero\Models\Product;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function save(Product $product): Product
    {
        $product->save();

        return $product->refresh();
    }

    public function findById(int $id): ?Product
    {
        return Product::query()->find($id);
    }
}
