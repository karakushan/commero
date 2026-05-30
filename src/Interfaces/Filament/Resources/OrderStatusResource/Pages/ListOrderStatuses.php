<?php

namespace Commero\Interfaces\Filament\Resources\OrderStatusResource\Pages;

use Commero\Interfaces\Filament\Resources\OrderStatusResource;
use Filament\Resources\Pages\ListRecords;

class ListOrderStatuses extends ListRecords
{
    protected static string $resource = OrderStatusResource::class;
}
