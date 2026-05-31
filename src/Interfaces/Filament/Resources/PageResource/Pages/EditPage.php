<?php

namespace Commero\Interfaces\Filament\Resources\PageResource\Pages;

use Commero\Interfaces\Filament\Resources\PageResource;
use Commero\Interfaces\Filament\Resources\PageResource\Pages\Concerns\InteractsWithPageTranslations;
use Commero\Models\Page;
use Commero\Support\Locales;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPage extends EditRecord
{
    use InteractsWithPageTranslations;

    protected static string $resource = PageResource::class;

    public function mount(int|string $record): void
    {
        $this->initializeActiveLocale();

        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewPage')
                ->label(__('commero::admin.page.actions.view_on_site'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): string => $this->getFrontendPageUrl())
                ->openUrlInNewTab(),
        ];
    }

    private function getFrontendPageUrl(): string
    {
        /** @var Page $page */
        $page = $this->getRecord()->loadMissing('translations');
        $activeLocale = $this->resolveActiveLocale();
        $defaultLocale = Locales::default();
        $activeTranslation = $page->translation($activeLocale);

        if (filled($activeTranslation?->slug)) {
            return $this->buildFrontendPageUrl($activeLocale, $activeTranslation->slug);
        }

        $defaultTranslation = $page->translation($defaultLocale);

        if (filled($defaultTranslation?->slug)) {
            return $this->buildFrontendPageUrl(
                Locales::isDefault($activeLocale) ? $defaultLocale : $activeLocale,
                $defaultTranslation->slug,
            );
        }

        $translationWithSlug = $page->translations
            ->first(fn ($translation): bool => filled($translation->slug));

        if (filled($translationWithSlug?->slug) && filled($translationWithSlug?->locale)) {
            return $this->buildFrontendPageUrl(
                Locales::isDefault($activeLocale) ? $translationWithSlug->locale : $activeLocale,
                $translationWithSlug->slug,
            );
        }

        return route('home');
    }

    private function buildFrontendPageUrl(string $locale, string $slug): string
    {
        return Locales::path('/'.$slug, $locale);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Page $record */
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
        /** @var Page $record */
        $record = $this->getRecord();

        return $this->preparePageDataForActiveLocale($data, $record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Page $record */
        $translations = $data['translations'] ?? [];
        unset($data['translations']);

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
