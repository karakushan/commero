<?php

namespace Commero\Models;

use Commero\Support\Concerns\HasCommeroMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductImage extends Model implements HasMedia
{
    use HasFactory;
    use HasCommeroMedia;

    protected $fillable = ['product_id', 'path', 'alt', 'sort', 'is_primary'];

    protected $casts = [
        'is_primary' => 'bool',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function commeroMediaCollections(): array
    {
        return ['product_gallery' => 'path'];
    }

    public function registerMediaCollections(): void
    {
        $this->addConfiguredMediaCollection('product_gallery');
    }
}
