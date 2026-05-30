<?php

namespace Commero\Interfaces\Filament\Resources\CategoryResource\Pages;

use Commero\Interfaces\Filament\Resources\CategoryResource;
use Commero\Interfaces\Filament\Resources\CategoryResource\Pages\Concerns\InteractsWithCategoryTranslations;
use Commero\Models\Category;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCategory extends CreateRecord
{
    use InteractsWithCategoryTranslations;

    protected static string $resource = CategoryResource::class;

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
        return $this->mutatePathData($this->prepareCategoryData($data));
    }

    protected function handleRecordCreation(array $data): Model
    {
        $translations = $data['translations'] ?? [];
        unset($data['translations'], $data['active_locale_context']);

        $category = new Category($data);
        $category->save();

        $this->syncTranslations($category, $translations);

        return $category;
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
