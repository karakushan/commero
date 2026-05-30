<?php

namespace Commero\Interfaces\Filament\Resources\MarketingLeadResource\Pages;

use Commero\Interfaces\Filament\Resources\MarketingLeadResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMarketingLead extends ViewRecord
{
    protected static string $resource = MarketingLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
