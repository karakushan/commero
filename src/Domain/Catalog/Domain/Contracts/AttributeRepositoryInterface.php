<?php

namespace Commero\Domain\Catalog\Domain\Contracts;

use Commero\Models\ProductAttribute;

interface AttributeRepositoryInterface
{
    public function save(ProductAttribute $attribute): ProductAttribute;
}
