<?php

namespace Commero\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'attribute_id',
        'value_string',
        'value_integer',
        'value_numeric',
        'value_boolean',
        'value_option_id',
        'value_json',
        'sort',
        'is_priority',
    ];

    protected $casts = [
        'value_boolean' => 'bool',
        'value_json' => 'array',
        'value_numeric' => 'decimal:3',
        'sort' => 'integer',
        'is_priority' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'value_option_id');
    }
}
