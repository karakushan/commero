<?php

namespace Commero\Interfaces\Filament\Resources\CurrencyResource\Pages;

use Commero\Interfaces\Filament\Resources\CurrencyResource;
use Filament\Resources\Pages\ListRecords;

class ListCurrencies extends ListRecords
{
    protected static string $resource = CurrencyResource::class;
}
