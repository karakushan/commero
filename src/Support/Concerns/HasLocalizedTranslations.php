<?php

namespace Commero\Support\Concerns;

use Commero\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasLocalizedTranslations
{
    abstract public function translations(): HasMany;

    public function translation(string $locale): ?object
    {
        $translations = $this->relationLoaded('translations')
            ? $this->getRelation('translations')
            : $this->translations()->whereIn('locale', Locales::preferred($locale))->get();

        return $translations->firstWhere('locale', $locale)
            ?? $translations->firstWhere('locale', Locales::fallback());
    }

    public function exactTranslation(string $locale): ?object
    {
        if ($this->relationLoaded('translations')) {
            $loadedTranslation = $this->getRelation('translations')->firstWhere('locale', $locale);

            if ($loadedTranslation) {
                return $loadedTranslation;
            }
        }

        return $this->translations()
            ->where('locale', $locale)
            ->first();
    }

    public function localizedSlug(string $locale, ?string $fallback = null): ?string
    {
        $currentSlug = $this->exactTranslation($locale)?->slug;

        if (filled($currentSlug)) {
            return $currentSlug;
        }

        $defaultSlug = $this->exactTranslation(Locales::default())?->slug;

        if (filled($defaultSlug)) {
            return $defaultSlug;
        }

        $loadedFallbackSlug = $this->relationLoaded('translations')
            ? $this->getRelation('translations')
                ->pluck('slug')
                ->first(fn (?string $slug): bool => filled($slug))
            : null;

        if (filled($loadedFallbackSlug)) {
            return $loadedFallbackSlug;
        }

        $queriedFallbackSlug = $this->translations()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->value('slug');

        return filled($queriedFallbackSlug)
            ? $queriedFallbackSlug
            : $fallback;
    }

    public function scopeWhereLocalizedSlug(Builder $query, string $slug, string $locale): Builder
    {
        $defaultLocale = Locales::default();

        return $query->where(function (Builder $builder) use ($slug, $locale, $defaultLocale): void {
            $builder->whereHas('translations', function ($translations) use ($slug, $locale): void {
                $translations
                    ->where('locale', $locale)
                    ->where('slug', $slug);
            });

            if ($locale !== $defaultLocale) {
                $builder->orWhereHas('translations', function ($translations) use ($slug, $defaultLocale): void {
                    $translations
                        ->where('locale', $defaultLocale)
                        ->where('slug', $slug);
                });
            }
        });
    }

    public function scopeWithTranslationsFor($query, string $locale)
    {
        return $query->with([
            'translations' => fn ($translations) => $translations->whereIn('locale', Locales::preferred($locale)),
        ]);
    }
}
