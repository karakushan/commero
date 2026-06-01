<?php

declare(strict_types=1);

namespace Commero\Policies;

class MarketingLeadPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'MarketingLead';
    }
}
