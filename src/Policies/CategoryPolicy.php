<?php

declare(strict_types=1);

namespace Commero\Policies;

class CategoryPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'Category';
    }
}
