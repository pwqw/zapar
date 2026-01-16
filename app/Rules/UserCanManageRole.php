<?php

namespace App\Rules;

use App\Enums\Acl\Role;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class UserCanManageRole implements ValidationRule
{
    public function __construct(private readonly User $user)
    {
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $roleToCreate = Role::from($value);

        // Users can only manage roles strictly lower than their own
        if (!$this->user->role->greaterThan($roleToCreate)) {
            $fail("The role $value is not manageable by the current user's role {$this->user->role->value}.");
        }
    }
}
