<?php

namespace Commero\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = ['code', 'name', 'symbol', 'is_base', 'rate', 'sort'];

    protected $casts = [
        'is_base' => 'bool',
        'rate' => 'decimal:6',
    ];

    public static function getBase(): ?self
    {
        return static::where('is_base', true)->first()
            ?? static::where('code', 'UAH')->first();
    }

    public static function getBaseSymbol(): string
    {
        return static::getBase()?->symbol ?? '₴';
    }

    public static function getBaseCode(): string
    {
        return static::getBase()?->code ?? 'UAH';
    }
}
