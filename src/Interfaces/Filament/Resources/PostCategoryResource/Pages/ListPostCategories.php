<?php

namespace Commero\Interfaces\Filament\Resources\PostCategoryResource\Pages;

use Commero\Interfaces\Filament\Resources\PostCategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListPostCategories extends ListRecords
{
    protected static string $resource = PostCategoryResource::class;
}
