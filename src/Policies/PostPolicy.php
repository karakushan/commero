<?php

declare(strict_types=1);

namespace Commero\Policies;

class PostPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'Post';
    }
}
