<?php

declare(strict_types=1);

namespace Commero\Policies;

class PostCategoryPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'PostCategory';
    }
}
