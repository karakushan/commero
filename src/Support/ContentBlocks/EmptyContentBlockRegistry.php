<?php

namespace Commero\Support\ContentBlocks;

use Commero\Contracts\ContentBlockRegistry;

class EmptyContentBlockRegistry implements ContentBlockRegistry
{
    public function builderBlocks(): array
    {
        return [];
    }

    public function viewMap(): array
    {
        return [];
    }
}
