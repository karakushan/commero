<?php

namespace Commero\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'locale', 'name', 'slug', 'description', 'full_description', 'meta_title', 'meta_description', 'robots'];

    protected function fullDescription(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): mixed {
                if (! is_string($value) || trim($value) === '') {
                    return $value;
                }

                $decoded = json_decode($value, true);

                return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
                    ? $decoded
                    : $value;
            },
            set: function (mixed $value): mixed {
                if (! is_array($value)) {
                    return $value;
                }

                return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            },
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
