<?php

declare(strict_types=1);

namespace Commero\Policies;

class AttributeGroupPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'AttributeGroup';
    }
}
