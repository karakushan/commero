<?php

namespace Commero\Interfaces\Filament\Resources\ShippingMethodResource\Pages;

use Commero\Interfaces\Filament\Resources\ShippingMethodResource;
use Commero\Interfaces\Filament\Resources\ShippingMethodResource\Pages\Concerns\InteractsWithShippingMethodTranslations;
use Commero\Models\ShippingMethod;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateShippingMethod extends CreateRecord
{
    use InteractsWithShippingMethodTranslations;

    protected static string $resource = ShippingMethodResource::class;

    public function mount(): void
    {
        $this->initializeActiveLocale();

        parent::mount();

        $this->form->fill([
            ...($this->data ?? []),
            ...$this->getActiveLocaleContextState(),
            'translations' => $this->getTranslationsFormState(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->prepareShippingMethodData($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $translations = $data['translations'] ?? [];

        unset($data['translations'], $data['active_locale_context']);

        $shippingMethod = new ShippingMethod($data);
        $shippingMethod->save();

        $this->syncTranslations($shippingMethod, $translations);

        return $shippingMethod;
    }

    protected function getRedirectUrlParameters(): array
    {
        return [
            'lang' => $this->resolveActiveLocale(),
        ];
    }
}
