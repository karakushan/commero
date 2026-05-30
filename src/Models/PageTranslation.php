<?php

namespace Commero\Models;

use Commero\Support\EntityLinkService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'locale',
        'title',
        'slug',
        'excerpt',
        'content',
        'blocks',
        'background_desktop_image',
        'background_mobile_image',
        'background_desktop_color',
        'background_mobile_color',
        'show_breadcrumbs',
        'show_title',
        'meta_title',
        'meta_description',
        'robots',
    ];

    protected $casts = [
        'blocks' => 'array',
        'show_breadcrumbs' => 'boolean',
        'show_title' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $translation): mixed => app(EntityLinkService::class)->syncPageTranslation($translation));
        static::deleted(fn (self $translation): mixed => app(EntityLinkService::class)->deletePageTranslation($translation));
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
