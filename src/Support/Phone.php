<?php

namespace Commero\Support;

class Phone
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return $digits !== '' ? $digits : null;
    }

    public static function isValidUkrainian(?string $value): bool
    {
        $digits = static::normalize($value);

        return $digits !== null
            && strlen($digits) === 12
            && str_starts_with($digits, '380');
    }
}
