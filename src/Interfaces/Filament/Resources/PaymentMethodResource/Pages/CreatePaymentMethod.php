<?php

namespace Commero\Interfaces\Filament\Resources\PaymentMethodResource\Pages;

use Commero\Interfaces\Filament\Resources\PaymentMethodResource;
use Commero\Interfaces\Filament\Resources\PaymentMethodResource\Pages\Concerns\InteractsWithPaymentMethodTranslations;
use Commero\Models\PaymentMethod;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePaymentMethod extends CreateRecord
{
    use InteractsWithPaymentMethodTranslations;

    protected static string $resource = PaymentMethodResource::class;

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
        return $this->preparePaymentMethodData($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $translations = $data['translations'] ?? [];

        unset($data['translations'], $data['active_locale_context']);

        $paymentMethod = new PaymentMethod($data);
        $paymentMethod->save();

        $this->syncTranslations($paymentMethod, $translations);

        return $paymentMethod;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRedirectUrlParameters(): array
    {
        return [
            'lang' => $this->resolveActiveLocale(),
        ];
    }
}
