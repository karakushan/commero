<?php

namespace Commero\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Link extends Model
{
    use HasFactory;

    public const ENTITY_CATEGORY = 'category';

    public const ENTITY_CITY_CATEGORY = 'city_category';

    public const ENTITY_PAGE = 'page';

    protected $fillable = [
        'locale',
        'slug',
        'entity_type',
        'entity_id',
    ];

    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    public function scopeForEntity(Builder $query, string $entityType, int|string $entityId): Builder
    {
        return $query
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    public static function normalizeSlug(?string $value): string
    {
        return Str::slug((string) $value);
    }

    /**
     * @param  array<int, string>  $reservedSlugs
     */
    public static function generateUniqueSlug(
        ?string $value,
        string $locale,
        ?string $entityType = null,
        int|string|null $entityId = null,
        array $reservedSlugs = [],
    ): ?string {
        $baseSlug = static::normalizeSlug($value);

        if ($baseSlug === '') {
            return null;
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (in_array($slug, $reservedSlugs, true) || static::localizedSlugExists($slug, $locale, $entityType, $entityId)) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    protected static function localizedSlugExists(
        string $slug,
        string $locale,
        ?string $entityType = null,
        int|string|null $entityId = null,
    ): bool {
        return static::query()
            ->forLocale($locale)
            ->where('slug', $slug)
            ->when(
                $entityType !== null && $entityId !== null,
                fn (Builder $query): Builder => $query->where(function (Builder $builder) use ($entityType, $entityId): void {
                    $builder
                        ->where('entity_type', '!=', $entityType)
                        ->orWhere('entity_id', '!=', $entityId);
                }),
            )
            ->exists();
    }
}
