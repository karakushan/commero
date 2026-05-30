<?php

namespace Commero\Interfaces\Filament\Resources\MarketingLeadResource\Pages;

use Commero\Interfaces\Filament\Resources\MarketingLeadResource;
use Filament\Resources\Pages\EditRecord;

class EditMarketingLead extends EditRecord
{
    protected static string $resource = MarketingLeadResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === 'processed' && empty($data['processed_at'])) {
            $data['processed_at'] = now();
        }

        if (($data['status'] ?? null) !== 'processed') {
            $data['processed_at'] = null;
        }

        return $data;
    }
}
