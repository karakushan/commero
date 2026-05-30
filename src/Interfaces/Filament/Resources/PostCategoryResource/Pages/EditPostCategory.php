<?php

namespace Commero\Interfaces\Filament\Resources\PostCategoryResource\Pages;

use Commero\Interfaces\Filament\Resources\PostCategoryResource;
use Commero\Interfaces\Filament\Resources\PostCategoryResource\Pages\Concerns\InteractsWithPostCategoryTranslations;
use Commero\Models\PostCategory;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPostCategory extends EditRecord
{
    use InteractsWithPostCategoryTranslations;

    protected static string $resource = PostCategoryResource::class;

    public function mount(int | string $record): void
    {
        $this->initializeActiveLocale();

        parent::mount($record);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PostCategory $record */
        $record = $this->getRecord()->load('translations');

        $data = [
            ...$data,
            ...$this->getActiveLocaleContextState(),
        ];
        $data['translations'] = $this->getTranslationsFormState($record);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var PostCategory $record */
        $record = $this->getRecord();

        return $this->preparePostCategoryDataForActiveLocale($data, $record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var PostCategory $record */
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
