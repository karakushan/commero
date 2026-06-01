<?php

declare(strict_types=1);

namespace Commero\Policies;

class RolePolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'Role';
    }
}
