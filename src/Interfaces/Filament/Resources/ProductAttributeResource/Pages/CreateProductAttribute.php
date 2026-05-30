<?php

namespace Commero\Interfaces\Filament\Resources\ProductAttributeResource\Pages;

use Commero\Interfaces\Filament\Resources\ProductAttributeResource;
use Commero\Interfaces\Filament\Resources\ProductAttributeResource\Pages\Concerns\InteractsWithProductAttributeTranslations;
use Commero\Models\ProductAttribute;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProductAttribute extends CreateRecord
{
    use InteractsWithProductAttributeTranslations;

    protected static string $resource = ProductAttributeResource::class;

    public function mount(): void
    {
        $this->initializeActiveLocale();

        parent::mount();

        $this->form->fill([
            ...($this->data ?? []),
            ...$this->getActiveLocaleContextState(),
            'translations' => $this->getTranslationsFormState(),
            'options' => $this->getOptionsFormState(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->prepareAttributeData($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $translations = $data['translations'] ?? [];
        $options = $data['options'] ?? [];

        unset($data['translations'], $data['options'], $data['active_locale_context']);

        $attribute = new ProductAttribute($data);
        $attribute->save();

        $this->syncTranslations($attribute, $translations);
        $this->syncOptions($attribute, $options);

        return $attribute;
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
