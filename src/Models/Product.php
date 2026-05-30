<?php

namespace Commero\Models;

use Commero\Support\Concerns\HasLocalizedTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;
    use HasLocalizedTranslations;

    protected $fillable = ['uuid', 'brand_id', 'type', 'status', 'sku', 'stock_status', 'views_count', 'attribute_snapshot', 'search_text', 'is_hit_sales', 'is_on_sale', 'is_new'];

    protected $casts = [
        'attribute_snapshot' => 'array',
        'is_hit_sales' => 'boolean',
        'is_on_sale' => 'boolean',
        'is_new' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $product): void {
            $product->uuid ??= (string) Str::uuid();
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->orderBy('sort')
            ->orderBy('id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)
            ->where('is_primary', true)
            ->orderBy('sort')
            ->orderBy('id');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class)->orderBy('sort')->orderBy('id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function viewSessions(): HasMany
    {
        return $this->hasMany(ProductViewSession::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ProductFaq::class)->orderBy('sort')->orderBy('id');
    }

    public function waitlistSubscriptions(): HasMany
    {
        return $this->hasMany(ProductWaitlistSubscription::class)->orderByDesc('created_at');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->orderByDesc('created_at');
    }

    public function colorRelatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(static::class, 'product_relations', 'product_id', 'related_product_id')
            ->wherePivot('type', 'color')
            ->withPivot(['type', 'sort'])
            ->withTimestamps()
            ->orderByPivot('sort')
            ->orderBy('products.id');
    }

    public function boughtTogetherProducts(): BelongsToMany
    {
        return $this->belongsToMany(static::class, 'product_relations', 'product_id', 'related_product_id')
            ->wherePivot('type', 'bought_together')
            ->withPivot(['type', 'sort'])
            ->withTimestamps()
            ->orderByPivot('sort')
            ->orderBy('products.id');
    }

    public function hasInStockVariants(): bool
    {
        if ($this->type !== 'variant') {
            return ($this->stock_status ?? 'in_stock') === 'in_stock';
        }

        $variants = $this->relationLoaded('variants')
            ? $this->variants
            : $this->variants()->get(['id', 'status']);

        return $variants->contains(fn (ProductVariant $variant): bool => $variant->status === 'in_stock');
    }

    public function effectiveStockStatus(): string
    {
        if ($this->type !== 'variant') {
            return $this->stock_status ?? 'in_stock';
        }

        return $this->hasInStockVariants() ? 'in_stock' : 'out_of_stock';
    }
}
