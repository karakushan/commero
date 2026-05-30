<?php

namespace Commero\Models;

use Commero\Support\Concerns\HasLocalizedTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
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
        return $this->hasMany(PaymentMethodTranslation::class);
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
}
