<?php

namespace Commero\Models;

use Commero\Support\Concerns\HasLocalizedTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostCategory extends Model
{
    use HasFactory;
    use HasLocalizedTranslations;

    protected $fillable = ['parent_id', 'path', 'depth', 'sort'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PostCategoryTranslation::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
