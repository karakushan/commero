<?php

namespace Commero\Models;

use Commero\Support\Concerns\HasLocalizedTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ShippingMethod extends Model
{
    use HasFactory;
    use HasLocalizedTranslations;

    protected $fillable = [
        'code',
        'name',
        'icon',
        'description',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(ShippingMethodTranslation::class);
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $this->translation(app()->getLocale())?->name ?? $value,
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $this->translation(app()->getLocale())?->description ?? $value,
        );
    }

    public function isNovaPoshta(): bool
    {
        $code = Str::lower((string) $this->code);
        $name = Str::lower((string) $this->name);

        return Str::contains($code, ['nova-poshta', 'nova_poshta', 'novaposhta'])
            || Str::contains($name, ['nova poshta', 'нова пошта']);
    }
}
