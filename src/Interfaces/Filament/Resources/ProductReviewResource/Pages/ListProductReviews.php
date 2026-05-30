<?php

namespace Commero\Interfaces\Filament\Resources\ProductReviewResource\Pages;

use Commero\Interfaces\Filament\Resources\ProductReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListProductReviews extends ListRecords
{
    protected static string $resource = ProductReviewResource::class;

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
