<?php

namespace App\Rules;

use App\Enums\Acl\Role;
use App\Facades\License;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AvailableRole implements ValidationRule
{
    public function __construct(
        private readonly ?User $userBeingUpdated = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!License::isCommunity()) {
            return;
        }

        $role = Role::tryFrom((string) $value);

        if ($role !== Role::MANAGER) {
            return;
        }

        if (
            $this->userBeingUpdated
            && $this->userBeingUpdated->role === Role::MANAGER
            && $role === Role::MANAGER
        ) {
            return;
        }

        $fail(__('validation.in', ['attribute' => $attribute]));
    }
}
