<?php

declare(strict_types=1);

namespace Commero\Policies;

class CurrencyPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'Currency';
    }
}
