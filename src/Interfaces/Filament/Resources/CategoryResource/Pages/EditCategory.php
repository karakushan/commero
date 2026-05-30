<?php

namespace Commero\Interfaces\Filament\Resources\CategoryResource\Pages;

use Commero\Interfaces\Filament\Resources\CategoryResource;
use Commero\Interfaces\Filament\Resources\CategoryResource\Pages\Concerns\InteractsWithCategoryTranslations;
use Commero\Models\Category;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCategory extends EditRecord
{
    use InteractsWithCategoryTranslations;

    protected static string $resource = CategoryResource::class;

    public function mount(int | string $record): void
    {
        $this->initializeActiveLocale();

        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewCategory')
                ->label(__('admin.category.actions.view_on_site'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): ?string => CategoryResource::getFrontendCategoryUrl($this->getRecord(), $this->resolveActiveLocale()))
                ->openUrlInNewTab()
                ->hidden(fn (): bool => blank(CategoryResource::getFrontendCategoryUrl($this->getRecord(), $this->resolveActiveLocale()))),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Category $record */
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
        /** @var Category $record */
        $record = $this->getRecord();

        return $this->prepareCategoryDataForActiveLocale($data, $record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Category $record */
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
