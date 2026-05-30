<?php

namespace Commero\Domain\Catalog\Infrastructure\Repositories;

use Commero\Domain\Catalog\Domain\Contracts\AttributeRepositoryInterface;
use Commero\Models\ProductAttribute;

class EloquentAttributeRepository implements AttributeRepositoryInterface
{
    public function save(ProductAttribute $attribute): ProductAttribute
    {
        $attribute->save();

        return $attribute->refresh();
    }
}
