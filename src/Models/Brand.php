<?php

namespace Commero\Models;

use Commero\Support\Concerns\HasLocalizedTranslations;
use Commero\Support\Locales;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;
    use HasLocalizedTranslations;

    protected $fillable = ['code', 'name', 'slug'];

    protected static function booted(): void
    {
        static::saved(function (self $brand): void {
            $name = $brand->getAttributes()['name'] ?? null;

            if (blank($name)) {
                return;
            }

            $brand->translations()->updateOrCreate(
                ['locale' => Locales::default()],
                ['name' => $name],
            );
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BrandTranslation::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $this->translation(app()->getLocale())?->name ?? $value,
        );
    }
}
