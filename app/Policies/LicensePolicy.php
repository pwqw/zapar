<?php

namespace App\Policies;

use App\Enums\Acl\Permission;
use App\Models\User;

class LicensePolicy
{
    public function activate(User $user): bool
    {
        // License activation is not available - Koel Plus is always enabled
        return false;
    }
}
