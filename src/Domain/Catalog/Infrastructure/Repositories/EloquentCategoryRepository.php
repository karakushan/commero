<?php

namespace Commero\Domain\Catalog\Infrastructure\Repositories;

use Commero\Domain\Catalog\Domain\Contracts\CategoryRepositoryInterface;
use Commero\Models\Category;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function save(Category $category): Category
    {
        $category->save();

        return $category->refresh();
    }
}
