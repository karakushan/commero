<?php

namespace Commero\Interfaces\Filament\Resources\ProductAttributeResource\Pages;

use Commero\Interfaces\Filament\Resources\ProductAttributeResource;
use Filament\Resources\Pages\ListRecords;

class ListProductAttributes extends ListRecords
{
    protected static string $resource = ProductAttributeResource::class;
}
