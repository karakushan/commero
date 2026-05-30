<?php

namespace Commero\Models;

use Commero\Support\Concerns\HasLocalizedTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatus extends Model
{
    use HasLocalizedTranslations;

    protected $fillable = ['code', 'name', 'color', 'text_color', 'icon', 'sort', 'is_active', 'is_default_for_new_order'];

    protected $casts = [
        'is_active' => 'bool',
        'is_default_for_new_order' => 'bool',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(OrderStatusTranslation::class);
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                return $this->translation(app()->getLocale())?->name
                    ?? $value;
            }
        );
    }
}
