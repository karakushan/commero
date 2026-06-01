<?php

declare(strict_types=1);

namespace Commero\Policies;

class PaymentMethodPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'PaymentMethod';
    }
}
