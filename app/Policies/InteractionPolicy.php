<?php

namespace App\Policies;

use App\Models\User;

class InteractionPolicy
{
    public function create(User $user): bool
    {
        // Anonymous users cannot create interactions (favorites)
        return !str_ends_with($user->email, '@' . User::ANONYMOUS_USER_DOMAIN);
    }
}
