<?php

declare(strict_types=1);

namespace Commero\Policies;

class ShippingMethodPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'ShippingMethod';
    }
}
