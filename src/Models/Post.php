<?php

namespace Commero\Models;

use Commero\Support\Concerns\HasLocalizedTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

class Post extends Model implements HasMedia
{
    use HasFactory;
    use HasLocalizedTranslations;
    use \Commero\Support\Concerns\HasCommeroMedia;

    protected $fillable = [
        'post_category_id',
        'status',
        'published_at',
        'sort',
        'thumbnail_path',
        'search_text',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function commeroMediaCollections(): array
    {
        return ['post_thumbnail' => 'thumbnail_path'];
    }

    public function registerMediaCollections(): void
    {
        $this->addConfiguredMediaCollection('post_thumbnail');
    }
}
