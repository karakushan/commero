<?php

declare(strict_types=1);

namespace Commero\Policies;

class CityCategoryPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'CityCategory';
    }
}
