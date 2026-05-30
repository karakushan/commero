<?php

namespace Commero\Interfaces\Filament\Resources\CityCategoryResource\Pages;

use Commero\Interfaces\Filament\Resources\CityCategoryResource;
use Commero\Interfaces\Filament\Resources\CityCategoryResource\Pages\Concerns\InteractsWithCityCategoryTranslations;
use Commero\Models\CityCategory;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCityCategory extends CreateRecord
{
    use InteractsWithCityCategoryTranslations;

    protected static string $resource = CityCategoryResource::class;

    public function mount(): void
    {
        $this->initializeActiveLocale();

        parent::mount();

        $this->form->fill([
            ...($this->data ?? []),
            ...$this->getActiveLocaleContextState(),
            'translations' => $this->getTranslationsFormState(),
            'display_category_ids' => [],
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->mutatePathData($this->prepareCityCategoryData($data));
    }

    protected function handleRecordCreation(array $data): Model
    {
        $translations = $data['translations'] ?? [];
        $displayCategoryIds = $data['display_category_ids'] ?? [];
        unset($data['translations'], $data['display_category_ids'], $data['active_locale_context']);

        $cityCategory = new CityCategory($data);
        $cityCategory->save();

        $this->syncTranslations($cityCategory, $translations);
        $this->syncDisplayCategories($cityCategory, $displayCategoryIds);

        return $cityCategory;
    }

    protected function getRedirectUrlParameters(): array
    {
        return [
            'lang' => $this->resolveActiveLocale(),
        ];
    }
}
