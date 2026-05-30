<?php

namespace Commero\Models;

use Commero\Support\Concerns\HasLocalizedTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttribute extends Model
{
    use HasFactory;
    use HasLocalizedTranslations;

    protected $table = 'attributes';

    protected $fillable = [
        'group_id',
        'code',
        'value_type',
        'is_filterable',
        'is_required',
        'is_variant_axis',
        'sort',
    ];

    protected $casts = [
        'is_filterable' => 'bool',
        'is_required' => 'bool',
        'is_variant_axis' => 'bool',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class, 'group_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(AttributeTranslation::class, 'attribute_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class, 'attribute_id');
    }
}
