<?php

namespace Commero\Interfaces\Filament\Resources\BrandResource\Pages;

use Commero\Interfaces\Filament\Resources\BrandResource;
use Commero\Interfaces\Filament\Resources\BrandResource\Pages\Concerns\InteractsWithBrandTranslations;
use Commero\Models\Brand;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBrand extends CreateRecord
{
    use InteractsWithBrandTranslations;

    protected static string $resource = BrandResource::class;

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
        return $this->prepareBrandData($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $translations = $data['translations'] ?? [];
        unset($data['translations'], $data['active_locale_context']);

        $brand = new Brand($data);
        $brand->save();

        $this->syncTranslations($brand, $translations);

        return $brand;
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
