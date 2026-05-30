<?php

namespace Commero\Interfaces\Filament\Resources\OrderResource\Pages;

use Commero\Interfaces\Filament\Resources\OrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
}
