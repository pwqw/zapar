<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SafeUrlOrInternalRoute implements ValidationRule
{
    /**
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return;
        }

        if (str_starts_with($trimmed, '/')) {
            if (preg_match('/[<>"\']/', $trimmed)) {
                $fail('The :attribute contains invalid characters for an internal route.');
            }

            return;
        }

        if (!filter_var($trimmed, FILTER_VALIDATE_URL)) {
            $fail('The :attribute must be a valid URL or an internal route starting with /.');

            return;
        }

        $scheme = parse_url(strtolower($trimmed), PHP_URL_SCHEME);
        $dangerousSchemes = ['javascript', 'data', 'vbscript', 'file', 'about'];

        if ($scheme && in_array($scheme, $dangerousSchemes, true)) {
            $fail('The :attribute field contains an unsafe URL scheme.');
        }
    }
}
