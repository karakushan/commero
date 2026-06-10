<?php

namespace Commero\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = ['code', 'name', 'symbol', 'country_codes', 'is_base', 'rate', 'sort'];

    protected $casts = [
        'country_codes' => 'array',
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

    public static function findByCountry(string $countryCode): ?self
    {
        return static::whereJsonContains('country_codes', mb_strtoupper($countryCode))->first();
    }

    public function convertToBase(float $amount): float
    {
        if ((float) $this->rate <= 0) {
            return $amount;
        }

        return $amount * (float) $this->rate;
    }

    public function convertFromBase(float $amount): float
    {
        if ((float) $this->rate <= 0) {
            return $amount;
        }

        return $amount / (float) $this->rate;
    }
}
