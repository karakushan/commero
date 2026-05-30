<?php

namespace Commero\Interfaces\Filament\Resources\MenuResource\Pages;

use Commero\Interfaces\Filament\Resources\MenuResource;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;
}
