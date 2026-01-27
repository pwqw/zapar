<?php

namespace App\Http\Requests\API;

/**
 * @property-read bool $terms_accepted
 * @property-read bool $privacy_accepted
 * @property-read bool $age_verified
 * @property-read string|null $locale
 */
class CreateAnonymousSessionRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'terms_accepted' => ['required', 'accepted'],
            'privacy_accepted' => ['required', 'accepted'],
            'age_verified' => ['required', 'accepted'],
            'locale' => ['sometimes', 'string', 'in:en,es'],
        ];
    }
}
