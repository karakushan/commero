<?php

declare(strict_types=1);

namespace Commero\Policies;

class BrandPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'Brand';
    }
}
