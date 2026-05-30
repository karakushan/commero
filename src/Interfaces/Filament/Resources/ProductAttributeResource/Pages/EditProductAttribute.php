<?php

namespace Commero\Interfaces\Filament\Resources\ProductAttributeResource\Pages;

use Commero\Interfaces\Filament\Resources\ProductAttributeResource;
use Commero\Interfaces\Filament\Resources\ProductAttributeResource\Pages\Concerns\InteractsWithProductAttributeTranslations;
use Commero\Models\ProductAttribute;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProductAttribute extends EditRecord
{
    use InteractsWithProductAttributeTranslations;

    protected static string $resource = ProductAttributeResource::class;

    public function mount(int | string $record): void
    {
        $this->initializeActiveLocale();

        parent::mount($record);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ProductAttribute $record */
        $record = $this->getRecord()->load(['translations', 'options.translations']);

        return [
            ...$data,
            ...$this->getActiveLocaleContextState(),
            'translations' => $this->getTranslationsFormState($record),
            'options' => $this->getOptionsFormState($record),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var ProductAttribute $record */
        $record = $this->getRecord();

        return $this->prepareAttributeDataForActiveLocale($data, $record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var ProductAttribute $record */
        $translations = $data['translations'] ?? [];
        $options = $data['options'] ?? [];

        unset($data['translations'], $data['options'], $data['active_locale_context']);

        $record->update($data);
        $this->syncTranslations($record, $translations);
        $this->syncOptions($record, $options);

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
