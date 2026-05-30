<?php

namespace Commero\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class ProductReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'parent_id',
        'locale',
        'display_name',
        'email',
        'author_type',
        'rating',
        'title',
        'comment',
        'status',
        'published_at',
        'moderated_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'published_at' => 'datetime',
        'moderated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $review): void {
            $review->locale = $review->locale ?: app()->getLocale();

            if ($review->parent_id !== null) {
                $parent = $review->parent()->first();

                if (! $parent) {
                    throw ValidationException::withMessages([
                        'parent_id' => __('validation.exists', ['attribute' => 'parent_id']),
                    ]);
                }

                if ($parent->parent_id !== null) {
                    throw ValidationException::withMessages([
                        'parent_id' => __('Reply depth exceeds the allowed level.'),
                    ]);
                }

                $review->product_id = $parent->product_id;
                $review->rating = null;
                $review->email = null;
            }

            if ($review->isDirty('status')) {
                if (in_array($review->status, ['approved', 'rejected'], true)) {
                    $review->moderated_at = now();
                }

                $review->published_at = $review->status === 'approved'
                    ? ($review->published_at ?? now())
                    : null;
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('created_at');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductReviewImage::class)->orderBy('sort')->orderBy('id');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }
}
