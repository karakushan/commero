<?php

namespace Commero\Interfaces\Filament\Resources\MenuResource\Pages\Concerns;

use Commero\Models\Menu;
use Commero\Models\MenuItem;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait InteractsWithMenuTranslations
{
    protected const ACTIVE_LOCALE_FIELD = 'active_locale_context';

    public string $activeLocale = '';

    protected function initializeActiveLocale(): void
    {
        $this->activeLocale = $this->resolveActiveLocale();
    }

    public function updatedActiveLocale(?string $locale): void
    {
        $this->activeLocale = in_array($locale, AdminLocales::supported(), true)
            ? (string) $locale
            : Locales::default();

        data_set($this, 'data.'.static::ACTIVE_LOCALE_FIELD, $this->activeLocale);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getActiveLocaleContextState(): array
    {
        return [
            static::ACTIVE_LOCALE_FIELD => $this->resolveActiveLocale(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getItemsFormState(?Menu $menu = null): array
    {
        if (! $menu) {
            return [];
        }

        return $menu->items
            ->sortBy('sort')
            ->values()
            ->map(function (MenuItem $item): array {
                return [
                    'id' => $item->id,
                    'sort' => $item->sort,
                    'is_active' => $item->is_active,
                    'open_in_new_tab' => $item->open_in_new_tab,
                    'translations' => $this->getItemTranslationsFormState($item),
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareMenuData(array $data): array
    {
        $items = $this->normalizeItems($data['items'] ?? []);

        return $this->finalizePreparedMenuData($data, $items);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareMenuDataForActiveLocale(array $data, ?Menu $menu = null): array
    {
        if (! $menu) {
            return $this->prepareMenuData($data);
        }

        $activeLocale = $this->resolveActiveLocale();
        $items = $this->normalizeItems($data['items'] ?? []);
        $existingItems = $menu->loadMissing('items.translations')->items->keyBy('id');

        foreach ($items as $index => $item) {
            $existingItem = filled($item['id'] ?? null)
                ? $existingItems->get((int) $item['id'])
                : null;

            if (! $existingItem) {
                continue;
            }

            $existingTranslations = $this->getItemTranslationsFormState($existingItem);

            foreach (AdminLocales::supported() as $locale) {
                if ($locale === $activeLocale) {
                    continue;
                }

                if ($this->hasMeaningfulTranslationData($existingTranslations[$locale] ?? [])) {
                    $items[$index]['translations'][$locale] = $existingTranslations[$locale];

                    continue;
                }

                unset($items[$index]['translations'][$locale]);
            }
        }

        return $this->finalizePreparedMenuData($data, $items);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function guardDefaultLocaleTranslations(array $items): void
    {
        $defaultLocale = Locales::default();

        $invalidItems = collect($items)
            ->map(function (array $item, int $index) use ($defaultLocale): ?int {
                $translation = $item['translations'][$defaultLocale] ?? [];

                $hasDefaultTranslation = filled($translation['label'] ?? null)
                    && filled($translation['url'] ?? null);

                return $hasDefaultTranslation ? null : $index + 1;
            })
            ->filter()
            ->values();

        if ($invalidItems->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'items' => __('commero::admin.menu.default_locale_required', [
                'locale' => $defaultLocale,
                'items' => $invalidItems->implode(', '),
            ]),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function syncItems(Menu $menu, array $items): void
    {
        $existingItems = $menu->items()->with('translations')->get()->keyBy('id');
        $incomingIds = collect($items)
            ->pluck('id')
            ->filter(fn (mixed $id): bool => filled($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $menu->items()
            ->when(
                $incomingIds === [],
                fn ($query) => $query,
                fn ($query) => $query->whereNotIn('id', $incomingIds),
            )
            ->delete();

        foreach (array_values($items) as $index => $itemData) {
            $item = filled($itemData['id'] ?? null)
                ? $existingItems->get((int) $itemData['id'])
                : null;

            $item ??= $menu->items()->make();

            $item->fill([
                'sort' => (int) ($itemData['sort'] ?? (($index + 1) * 10)),
                'is_active' => (bool) ($itemData['is_active'] ?? true),
                'open_in_new_tab' => (bool) ($itemData['open_in_new_tab'] ?? false),
            ]);

            $menu->items()->save($item);

            $this->syncItemTranslations($item, $itemData['translations'] ?? []);
        }
    }

    protected function resolveActiveLocale(?string $locale = null): string
    {
        $locale = $locale
            ?? data_get($this, 'data.'.static::ACTIVE_LOCALE_FIELD)
            ?? request()->query('lang')
            ?? $this->activeLocale;

        if (! in_array($locale, AdminLocales::supported(), true)) {
            $locale = Locales::default();
        }

        return $locale;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getItemTranslationsFormState(?MenuItem $item = null): array
    {
        $state = collect(AdminLocales::supported())
            ->mapWithKeys(fn (string $locale): array => [$locale => $this->emptyTranslation($locale)])
            ->all();

        if (! $item) {
            return $state;
        }

        foreach ($item->translations as $translation) {
            $state[$translation->locale] = array_merge(
                $this->emptyTranslation($translation->locale),
                Arr::only($translation->toArray(), [
                    'label',
                    'url',
                ]),
            );
        }

        return $state;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        return collect(array_values($items))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(function (array $item, int $index): array {
                return [
                    'id' => filled($item['id'] ?? null) ? (int) $item['id'] : null,
                    'sort' => is_numeric($item['sort'] ?? null) ? (int) $item['sort'] : (($index + 1) * 10),
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'open_in_new_tab' => (bool) ($item['open_in_new_tab'] ?? false),
                    'translations' => $this->normalizeTranslations($item['translations'] ?? []),
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function finalizePreparedMenuData(array $data, array $items): array
    {
        $this->guardDefaultLocaleTranslations($items);

        $data[static::ACTIVE_LOCALE_FIELD] = $this->resolveActiveLocale($data[static::ACTIVE_LOCALE_FIELD] ?? null);
        $data['items'] = $items;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, array<string, mixed>>
     */
    private function normalizeTranslations(array $translations): array
    {
        return collect(AdminLocales::supported())
            ->mapWithKeys(function (string $locale) use ($translations): array {
                $state = array_merge(
                    $this->emptyTranslation($locale),
                    Arr::only((array) ($translations[$locale] ?? []), [
                        'label',
                        'url',
                    ]),
                );

                return [
                    $locale => [
                        'locale' => $locale,
                        'label' => $this->normalizeTextValue($state['label'] ?? null, false),
                        'url' => $this->normalizeTextValue($state['url'] ?? null, false),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $translations
     */
    private function syncItemTranslations(MenuItem $item, array $translations): void
    {
        $existingTranslations = $item->translations()->get()->keyBy('locale');

        foreach (AdminLocales::supported() as $locale) {
            $translation = $translations[$locale] ?? [];
            $existingTranslation = $existingTranslations->get($locale);

            if (! $this->hasMeaningfulTranslationData($translation)) {
                $existingTranslation?->delete();

                continue;
            }

            $item->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'label' => (string) ($translation['label'] ?? ''),
                    'url' => (string) ($translation['url'] ?? ''),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $translation
     */
    private function hasMeaningfulTranslationData(array $translation): bool
    {
        return filled($translation['label'] ?? null)
            || filled($translation['url'] ?? null);
    }

    /**
     * @return array<string, string|null>
     */
    private function emptyTranslation(string $locale): array
    {
        return [
            'locale' => $locale,
            'label' => null,
            'url' => null,
        ];
    }

    private function normalizeTextValue(mixed $value, bool $nullable = true): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === null || $value === '') {
            return $nullable ? null : '';
        }

        return $value;
    }
}
