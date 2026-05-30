<?php

namespace Commero\Interfaces\Filament\Resources\CurrencyResource\Pages;

use Commero\Interfaces\Filament\Resources\CurrencyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCurrency extends CreateRecord
{
    protected static string $resource = CurrencyResource::class;
}
