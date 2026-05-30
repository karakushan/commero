<?php

namespace Commero\Interfaces\Filament\Resources\OrderResource\Pages;

use Commero\Interfaces\Filament\Resources\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
