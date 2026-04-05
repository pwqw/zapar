<?php

namespace App\Policies;

use App\Enums\Acl\Permission;
use App\Models\User;

class UserPolicy
{
    public function manage(User $currentUser): bool
    {
        return $this->hasUserManagementPermission($currentUser);
    }

    public function update(User $currentUser, User $userToUpdate): bool
    {
        return (
            $this->hasUserManagementPermission($currentUser)
            && $currentUser->role->canManage($userToUpdate->role)
        );
    }

    public function destroy(User $currentUser, User $userToDestroy): bool
    {
        return (
            $this->hasUserManagementPermission($currentUser)
            && $userToDestroy->isNot($currentUser)
            && $currentUser->role->canManage($userToDestroy->role)
        );
    }

    private function hasUserManagementPermission(User $currentUser): bool
    {
        return $currentUser->hasPermissionTo(Permission::MANAGE_ALL_USERS)
            || $currentUser->hasPermissionTo(Permission::MANAGE_ORG_USERS)
            || $currentUser->hasPermissionTo(Permission::MANAGE_ARTISTS);
    }

    public function verify(User $currentUser, User $target): bool
    {
        return $currentUser->canVerify($target);
    }

    public function upload(User $currentUser): bool
    {
        return true;
    }
}
