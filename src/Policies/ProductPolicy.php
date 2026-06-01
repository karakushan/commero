<?php

declare(strict_types=1);

namespace Commero\Policies;

class ProductPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'Product';
    }
}
