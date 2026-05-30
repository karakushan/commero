<?php

namespace Commero\Interfaces\Filament\Resources\CategoryResource\Pages;

use Commero\Interfaces\Filament\Resources\CategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;
}
