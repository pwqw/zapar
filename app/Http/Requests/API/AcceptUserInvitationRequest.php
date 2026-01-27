<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rules\Password;

/**
 * @property-read string $token
 * @property-read string $name
 * @property-read string $password
 * @property-read bool $terms_accepted
 * @property-read bool $privacy_accepted
 * @property-read bool $age_verified
 */
class AcceptUserInvitationRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'token' => 'required',
            'password' => ['required', Password::defaults()],
            'terms_accepted' => ['required', 'accepted'],
            'privacy_accepted' => ['required', 'accepted'],
            'age_verified' => ['required', 'accepted'],
        ];
    }
}
