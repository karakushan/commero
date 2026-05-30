<?php

namespace Commero\Models;

use Commero\Support\Concerns\HasLocalizedTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;
    use HasLocalizedTranslations;

    protected $fillable = [
        'menu_id',
        'sort',
        'is_active',
        'open_in_new_tab',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'open_in_new_tab' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MenuItemTranslation::class)->orderBy('locale');
    }

    public function label(?string $locale = null): ?string
    {
        return $this->translation($locale ?? app()->getLocale())?->label;
    }

    public function url(?string $locale = null): ?string
    {
        return $this->translation($locale ?? app()->getLocale())?->url;
    }
}
