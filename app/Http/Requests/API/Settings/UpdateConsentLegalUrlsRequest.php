<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;
use App\Rules\SafeUrlOrInternalRoute;

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
            'terms_url' => ['nullable', 'string', 'max:2000', new SafeUrlOrInternalRoute()],
            'privacy_url' => ['nullable', 'string', 'max:2000', new SafeUrlOrInternalRoute()],
        ];
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
