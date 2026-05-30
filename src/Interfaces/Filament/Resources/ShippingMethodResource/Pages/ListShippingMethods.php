<?php

namespace Commero\Interfaces\Filament\Resources\ShippingMethodResource\Pages;

use Commero\Interfaces\Filament\Resources\ShippingMethodResource;
use Filament\Resources\Pages\ListRecords;

class ListShippingMethods extends ListRecords
{
    protected static string $resource = ShippingMethodResource::class;
}
