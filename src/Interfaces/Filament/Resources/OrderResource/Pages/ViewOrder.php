<?php

namespace Commero\Interfaces\Filament\Resources\OrderResource\Pages;

use Commero\Interfaces\Filament\Resources\OrderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
