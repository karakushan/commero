<?php

namespace Commero\Interfaces\Filament\Resources\CityCategoryResource\Pages;

use Commero\Interfaces\Filament\Resources\CityCategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListCityCategories extends ListRecords
{
    protected static string $resource = CityCategoryResource::class;
}
