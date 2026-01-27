<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;
use Closure;

/**
 * @property-read string|null $terms_url
 * @property-read string|null $privacy_url
 */
class UpdateConsentLegalUrlsRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'terms_url' => ['nullable', 'string', 'max:2000', $this->urlOrInternalRoute()],
            'privacy_url' => ['nullable', 'string', 'max:2000', $this->urlOrInternalRoute()],
        ];
    }

    /** @return Closure(string, mixed, Closure): void */
    private function urlOrInternalRoute(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (!is_string($value)) {
                return;
            }
            $trimmed = trim($value);
            if ($trimmed === '') {
                return;
            }
            if (str_starts_with($trimmed, '/')) {
                if (preg_match('/[<>"\']/', $trimmed)) {
                    $fail('The value contains invalid characters for an internal route.');
                }
                return;
            }
            if (!filter_var($trimmed, FILTER_VALIDATE_URL)) {
                $fail('The value must be a valid URL or an internal route starting with / (e.g. /#/document/slug).');
                return;
            }
            $scheme = parse_url(strtolower($trimmed), PHP_URL_SCHEME);
            $dangerous = ['javascript', 'data', 'vbscript', 'file', 'about'];
            if ($scheme && in_array($scheme, $dangerous, true)) {
                $fail('The value contains an unsafe URL scheme.');
            }
        };
    }

    protected function prepareForValidation(): void
    {
        $trimmed = fn (string $v) => trim($v) !== '' ? trim($v) : null;
        $this->merge([
            'terms_url' => $this->filled('terms_url') ? $trimmed((string) $this->terms_url) : null,
            'privacy_url' => $this->filled('privacy_url') ? $trimmed((string) $this->privacy_url) : null,
        ]);
    }
}
