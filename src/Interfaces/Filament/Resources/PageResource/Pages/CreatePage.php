<?php

namespace Commero\Interfaces\Filament\Resources\PageResource\Pages;

use Commero\Interfaces\Filament\Resources\PageResource;
use Commero\Interfaces\Filament\Resources\PageResource\Pages\Concerns\InteractsWithPageTranslations;
use Commero\Models\Page;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePage extends CreateRecord
{
    use InteractsWithPageTranslations;

    protected static string $resource = PageResource::class;

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

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->preparePageData($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $translations = $data['translations'] ?? [];
        unset($data['translations']);

        $page = new Page($data);
        $page->save();

        $this->syncTranslations($page, $translations);

        return $page;
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
