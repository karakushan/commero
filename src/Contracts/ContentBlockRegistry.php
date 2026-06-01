<?php

namespace Commero\Contracts;

use Filament\Forms\Components\Builder\Block;

interface ContentBlockRegistry
{
    /**
     * @return array<int, Block>
     */
    public function builderBlocks(): array;

    /**
     * @return array<string, string>
     */
    public function viewMap(): array;
}
