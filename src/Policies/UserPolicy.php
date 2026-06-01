<?php

declare(strict_types=1);

namespace Commero\Policies;

class UserPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'User';
    }
}
