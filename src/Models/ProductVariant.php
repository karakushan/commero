<?php

namespace Commero\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'name', 'sku', 'barcode', 'price', 'old_price', 'multi_currency_code', 'multi_currency_price', 'multi_currency_old_price', 'stock_qty', 'status', 'option_snapshot', 'sort'];

    protected $casts = [
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'multi_currency_price' => 'decimal:2',
        'multi_currency_old_price' => 'decimal:2',
        'option_snapshot' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'variant_id');
    }
}
