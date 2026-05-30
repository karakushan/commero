<?php

namespace Commero\Interfaces\Filament\Resources\ProductResource\Pages;

use Commero\Interfaces\Filament\Resources\ProductResource;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function mount(): void
    {
        if (blank($this->tableFilters) && request()->has('tableFilters')) {
            $legacyFilters = request()->query('tableFilters');

            if (is_array($legacyFilters)) {
                $this->tableFilters = $legacyFilters;
            }
        }

        parent::mount();
    }
}
