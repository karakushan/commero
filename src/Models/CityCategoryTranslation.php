<?php

namespace Commero\Models;

use Commero\Support\EntityLinkService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CityCategoryTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['city_category_id', 'locale', 'name', 'slug', 'blocks', 'meta_title', 'meta_description', 'robots'];

    protected $casts = [
        'blocks' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $translation): mixed => app(EntityLinkService::class)->syncCityCategoryTranslation($translation));
        static::deleted(fn (self $translation): mixed => app(EntityLinkService::class)->deleteCityCategoryTranslation($translation));
    }

    public function cityCategory(): BelongsTo
    {
        return $this->belongsTo(CityCategory::class);
    }
}
