<?php

declare(strict_types=1);

namespace Commero\Policies;

class MenuPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'Menu';
    }
}
