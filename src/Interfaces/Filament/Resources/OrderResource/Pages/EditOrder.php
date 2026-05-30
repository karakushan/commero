<?php

namespace Commero\Interfaces\Filament\Resources\OrderResource\Pages;

use Commero\Interfaces\Filament\Resources\OrderResource;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;
}
