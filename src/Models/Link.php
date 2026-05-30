<?php

namespace Commero\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
