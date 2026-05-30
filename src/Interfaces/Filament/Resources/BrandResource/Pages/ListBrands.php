<?php

namespace Commero\Interfaces\Filament\Resources\BrandResource\Pages;

use Commero\Interfaces\Filament\Resources\BrandResource;
use Filament\Resources\Pages\ListRecords;

class ListBrands extends ListRecords
{
    protected static string $resource = BrandResource::class;
}
