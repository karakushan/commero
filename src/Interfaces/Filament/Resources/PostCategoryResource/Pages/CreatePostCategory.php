<?php

namespace Commero\Interfaces\Filament\Resources\PostCategoryResource\Pages;

use Commero\Interfaces\Filament\Resources\PostCategoryResource;
use Commero\Interfaces\Filament\Resources\PostCategoryResource\Pages\Concerns\InteractsWithPostCategoryTranslations;
use Commero\Models\PostCategory;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePostCategory extends CreateRecord
{
    use InteractsWithPostCategoryTranslations;

    protected static string $resource = PostCategoryResource::class;

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
        return $this->preparePostCategoryData($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $translations = $data['translations'] ?? [];
        unset($data['translations'], $data['active_locale_context']);

        $postCategory = new PostCategory($data);
        $postCategory->save();

        $this->syncTranslations($postCategory, $translations);

        return $postCategory;
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
