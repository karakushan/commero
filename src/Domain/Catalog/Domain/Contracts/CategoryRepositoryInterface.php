<?php

namespace Commero\Domain\Catalog\Domain\Contracts;

use Commero\Models\Category;

interface CategoryRepositoryInterface
{
    public function save(Category $category): Category;
}
