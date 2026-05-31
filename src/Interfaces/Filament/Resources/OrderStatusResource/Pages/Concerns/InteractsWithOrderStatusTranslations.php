<?php

namespace Commero\Interfaces\Filament\Resources\OrderStatusResource\Pages\Concerns;

use Commero\Models\OrderStatus;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait InteractsWithOrderStatusTranslations
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
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareOrderStatusData(array $data): array
    {
        $translations = $this->normalizeTranslations($data['translations'] ?? []);

        return $this->finalizePreparedOrderStatusData($data, $translations);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareOrderStatusDataForActiveLocale(array $data, ?OrderStatus $orderStatus = null): array
    {
        if (! $orderStatus) {
            return $this->prepareOrderStatusData($data);
        }

        $activeLocale = $this->resolveActiveLocale();
        $translations = $this->normalizeTranslations($data['translations'] ?? []);
        $existingTranslations = $this->getTranslationsFormState($orderStatus->loadMissing('translations'));

        foreach (AdminLocales::supported() as $locale) {
            if ($locale === $activeLocale) {
                continue;
            }

            if ($this->hasMeaningfulTranslationData($existingTranslations[$locale] ?? [])) {
                $translations[$locale] = $this->normalizeTranslations([
                    $locale => $existingTranslations[$locale],
                ])[$locale];

                continue;
            }

            unset($translations[$locale]);
        }

        return $this->finalizePreparedOrderStatusData($data, $translations);
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, array<string, mixed>>
     */
    protected function normalizeTranslations(array $translations): array
    {
        return collect(AdminLocales::supported())
            ->mapWithKeys(function (string $locale) use ($translations): array {
                $state = Arr::only((array) ($translations[$locale] ?? []), ['name']);

                return [
                    $locale => [
                        'locale' => $locale,
                        'name' => $this->normalizeTextValue($state['name'] ?? null, false),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getTranslationsFormState(?OrderStatus $orderStatus = null): array
    {
        $state = collect(AdminLocales::supported())
            ->mapWithKeys(fn (string $locale): array => [$locale => $this->emptyTranslation()])
            ->all();

        if (! $orderStatus) {
            return $state;
        }

        foreach ($orderStatus->translations as $translation) {
            $state[$translation->locale] = array_merge(
                $this->emptyTranslation(),
                Arr::only($translation->toArray(), ['name']),
            );
        }

        $defaultLocale = Locales::default();

        if (blank($state[$defaultLocale]['name'] ?? null) && filled($orderStatus->getRawOriginal('name'))) {
            $state[$defaultLocale]['name'] = $orderStatus->getRawOriginal('name');
        }

        return $state;
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
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, mixed>>  $translations
     * @return array<string, mixed>
     */
    protected function finalizePreparedOrderStatusData(array $data, array $translations): array
    {
        $this->guardDefaultLocaleTranslation($translations);

        $defaultLocale = Locales::default();
        $data[static::ACTIVE_LOCALE_FIELD] = $this->resolveActiveLocale($data[static::ACTIVE_LOCALE_FIELD] ?? null);
        $data['name'] = (string) ($translations[$defaultLocale]['name'] ?? '');
        $data['translations'] = $translations;

        return $data;
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function syncTranslations(OrderStatus $orderStatus, array $translations): void
    {
        $existingTranslations = $orderStatus->translations()->get()->keyBy('locale');

        foreach ($translations as $locale => $translation) {
            $existing = $existingTranslations->get($locale);

            if (! $this->hasMeaningfulTranslationData($translation)) {
                $existing?->delete();

                continue;
            }

            $orderStatus->translations()->updateOrCreate(
                ['locale' => $locale],
                ['name' => (string) ($translation['name'] ?? '')],
            );
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function guardDefaultLocaleTranslation(array $translations): void
    {
        $defaultLocale = Locales::default();
        $defaultTranslation = $translations[$defaultLocale] ?? [];

        if (blank($defaultTranslation['name'] ?? null)) {
            throw ValidationException::withMessages([
                "translations.{$defaultLocale}.name" => __('commero::admin.resources.order_status.default_locale_required', ['locale' => $defaultLocale]),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $translation
     */
    protected function hasMeaningfulTranslationData(array $translation): bool
    {
        return filled($translation['name'] ?? null);
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

    protected function normalizeTextValue(mixed $value, bool $nullable = true): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === null || $value === '') {
            return $nullable ? null : '';
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyTranslation(): array
    {
        return [
            'name' => null,
        ];
    }
}
