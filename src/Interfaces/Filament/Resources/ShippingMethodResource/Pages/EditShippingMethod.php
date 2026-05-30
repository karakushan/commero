<?php

namespace Commero\Interfaces\Filament\Resources\ShippingMethodResource\Pages;

use Commero\Interfaces\Filament\Resources\ShippingMethodResource;
use Commero\Interfaces\Filament\Resources\ShippingMethodResource\Pages\Concerns\InteractsWithShippingMethodTranslations;
use Commero\Models\ShippingMethod;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditShippingMethod extends EditRecord
{
    use InteractsWithShippingMethodTranslations;

    protected static string $resource = ShippingMethodResource::class;

    public function mount(int | string $record): void
    {
        $this->initializeActiveLocale();

        parent::mount($record);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ShippingMethod $record */
        $record = $this->getRecord()->load('translations');

        return [
            ...$data,
            ...$this->getActiveLocaleContextState(),
            'translations' => $this->getTranslationsFormState($record),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var ShippingMethod $record */
        $record = $this->getRecord();

        return $this->prepareShippingMethodDataForActiveLocale($data, $record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var ShippingMethod $record */
        $translations = $data['translations'] ?? [];

        unset($data['translations'], $data['active_locale_context']);

        $record->update($data);
        $this->syncTranslations($record, $translations);

        return $record->refresh();
    }

    protected function getRedirectUrlParameters(): array
    {
        return [
            'lang' => $this->resolveActiveLocale(),
        ];
    }
}
