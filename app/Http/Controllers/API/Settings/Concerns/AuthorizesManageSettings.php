<?php

namespace App\Http\Controllers\API\Settings\Concerns;

use App\Enums\Acl\Permission;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

trait AuthorizesManageSettings
{
    /** @param User $user */
    protected function authorizeManageSettings(Authenticatable $user): void
    {
        abort_unless(
            $user->hasPermissionTo(Permission::MANAGE_SETTINGS),
            Response::HTTP_FORBIDDEN,
        );
    }
}
