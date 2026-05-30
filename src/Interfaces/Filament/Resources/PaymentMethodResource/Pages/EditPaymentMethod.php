<?php

namespace Commero\Interfaces\Filament\Resources\PaymentMethodResource\Pages;

use Commero\Interfaces\Filament\Resources\PaymentMethodResource;
use Commero\Interfaces\Filament\Resources\PaymentMethodResource\Pages\Concerns\InteractsWithPaymentMethodTranslations;
use Commero\Models\PaymentMethod;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPaymentMethod extends EditRecord
{
    use InteractsWithPaymentMethodTranslations;

    protected static string $resource = PaymentMethodResource::class;

    public function mount(int | string $record): void
    {
        $this->initializeActiveLocale();

        parent::mount($record);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PaymentMethod $record */
        $record = $this->getRecord()->load('translations');

        return [
            ...$data,
            ...$this->getActiveLocaleContextState(),
            'translations' => $this->getTranslationsFormState($record),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var PaymentMethod $record */
        $record = $this->getRecord();

        return $this->preparePaymentMethodDataForActiveLocale($data, $record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var PaymentMethod $record */
        $translations = $data['translations'] ?? [];

        unset($data['translations'], $data['active_locale_context']);

        $record->update($data);
        $this->syncTranslations($record, $translations);

        return $record->refresh();
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
