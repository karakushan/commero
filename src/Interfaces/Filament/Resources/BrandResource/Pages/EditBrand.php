<?php

namespace Commero\Interfaces\Filament\Resources\BrandResource\Pages;

use Commero\Interfaces\Filament\Resources\BrandResource;
use Commero\Interfaces\Filament\Resources\BrandResource\Pages\Concerns\InteractsWithBrandTranslations;
use Commero\Models\Brand;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBrand extends EditRecord
{
    use InteractsWithBrandTranslations;

    protected static string $resource = BrandResource::class;

    public function mount(int|string $record): void
    {
        $this->initializeActiveLocale();

        parent::mount($record);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Brand $record */
        $record = $this->getRecord()->load('translations');

        return [
            ...$data,
            ...$this->getActiveLocaleContextState(),
            'translations' => $this->getTranslationsFormState($record),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Brand $record */
        $record = $this->getRecord();

        return $this->prepareBrandDataForActiveLocale($data, $record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Brand $record */
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
