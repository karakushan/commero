<?php

namespace Commero\Models;

use Commero\Support\Concerns\HasLocalizedTranslations;
use Commero\Support\EntityLinkService;
use Commero\Support\Locales;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;
    use HasLocalizedTranslations;

    protected $fillable = ['parent_id', 'path', 'depth', 'sort', 'icon_path', 'thumbnail_path'];

    protected $appends = ['url'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_category');
    }

    public function frontendUrl(?string $locale = null): ?string
    {
        $resolvedLocale = Locales::resolve($locale ?? app()->getLocale());

        $url = app(EntityLinkService::class)->categoryUrl($this, $resolvedLocale)
            ?? Locales::path('/'.($this->localizedSlug($resolvedLocale, $this->path) ?? $this->getKey()), $resolvedLocale);

        return Locales::ensureTrailingSlash($url);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->frontendUrl();
    }
}
