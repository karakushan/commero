<?php

namespace Commero\Support\ContentBlocks;

use Commero\Contracts\ContentBlockHydrator;

class NullContentBlockHydrator implements ContentBlockHydrator
{
    public function hydrate(array $blocks, string $locale): array
    {
        return $blocks;
    }
}
