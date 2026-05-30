<?php

namespace Commero\Interfaces\Filament\Resources\PaymentMethodResource\Pages;

use Commero\Interfaces\Filament\Resources\PaymentMethodResource;
use Filament\Resources\Pages\ListRecords;

class ListPaymentMethods extends ListRecords
{
    protected static string $resource = PaymentMethodResource::class;
}
