<?php

namespace Commero\Contracts;

interface ContentBlockHydrator
{
    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    public function hydrate(array $blocks, string $locale): array;
}
