<?php

namespace Commero\Interfaces\Filament\Resources\CityCategoryResource\Pages;

use Commero\Interfaces\Filament\Resources\CityCategoryResource;
use Commero\Interfaces\Filament\Resources\CityCategoryResource\Pages\Concerns\InteractsWithCityCategoryTranslations;
use Commero\Models\CityCategory;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCityCategory extends EditRecord
{
    use InteractsWithCityCategoryTranslations;

    protected static string $resource = CityCategoryResource::class;

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
                ->url(fn (): ?string => CityCategoryResource::getFrontendCategoryUrl($this->getRecord(), $this->resolveActiveLocale()))
                ->openUrlInNewTab()
                ->hidden(fn (): bool => blank(CityCategoryResource::getFrontendCategoryUrl($this->getRecord(), $this->resolveActiveLocale()))),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var CityCategory $record */
        $record = $this->getRecord()->load(['translations', 'categories']);

        $data = [
            ...$data,
            ...$this->getActiveLocaleContextState(),
        ];
        $data['translations'] = $this->getTranslationsFormState($record);
        $data['display_category_ids'] = $record->categories->pluck('id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var CityCategory $record */
        $record = $this->getRecord();

        return $this->mutatePathData($this->prepareCityCategoryDataForActiveLocale($data, $record), $record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var CityCategory $record */
        $translations = $data['translations'] ?? [];
        $displayCategoryIds = $data['display_category_ids'] ?? [];
        unset($data['translations'], $data['display_category_ids'], $data['active_locale_context']);

        $record->update($data);
        $this->syncTranslations($record, $translations);
        $this->syncDisplayCategories($record, $displayCategoryIds);

        return $record->refresh();
    }

    protected function getRedirectUrlParameters(): array
    {
        return [
            'lang' => $this->resolveActiveLocale(),
        ];
    }
}
