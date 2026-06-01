<?php

declare(strict_types=1);

namespace Commero\Policies;

class ProductReviewPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'ProductReview';
    }
}
