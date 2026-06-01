<?php

namespace Commero\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FlexibleUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('commero::validation.flexible_url', ['attribute' => $attribute]));

            return;
        }

        $value = trim($value);

        if ($value === '' || preg_match('/\s/u', $value) !== 0) {
            $fail(__('commero::validation.flexible_url', ['attribute' => $attribute]));

            return;
        }

        if (preg_match('/^[a-z][a-z0-9+\\-.]*:/i', $value) !== 1) {
            $fail(__('commero::validation.flexible_url', ['attribute' => $attribute]));

            return;
        }

        $parts = parse_url($value);

        if ($parts === false || empty($parts['scheme'])) {
            $fail(__('commero::validation.flexible_url', ['attribute' => $attribute]));

            return;
        }

        if (in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            && filter_var($value, FILTER_VALIDATE_URL) === false) {
            $fail(__('commero::validation.flexible_url', ['attribute' => $attribute]));
        }
    }
}
