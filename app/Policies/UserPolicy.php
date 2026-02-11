<?php

namespace App\Policies;

use App\Enums\Acl\Permission;
use App\Enums\Acl\Role;
use App\Models\User;

class UserPolicy
{
    public function manage(User $currentUser): bool
    {
        if ($currentUser->hasElevatedRole()) {
            return true;
        }

        return $currentUser->hasPermissionTo(Permission::MANAGE_ARTISTS);
    }

    public function update(User $currentUser, User $userToUpdate): bool
    {
        if ($currentUser->hasElevatedRole()) {
            return $currentUser->canManage($userToUpdate);
        }

        if ($currentUser->role === Role::MANAGER) {
            if ($currentUser->is($userToUpdate)) {
                return true;
            }

            return $this->isManagedArtist($currentUser, $userToUpdate)
                && $currentUser->canManage($userToUpdate);
        }

        return false;
    }

    public function edit(User $currentUser, User $userToEdit): bool
    {
        return $this->update($currentUser, $userToEdit);
    }

    public function destroy(User $currentUser, User $userToDestroy): bool
    {
        if ($currentUser->is($userToDestroy)) {
            return false;
        }

        if ($currentUser->hasElevatedRole()) {
            return $currentUser->canManage($userToDestroy);
        }

        if ($currentUser->role === Role::MANAGER) {
            return $this->isManagedArtist($currentUser, $userToDestroy)
                && $currentUser->canManage($userToDestroy);
        }

        return false;
    }

    public function upload(User $currentUser): bool
    {
        return $currentUser->role->level() >= Role::ARTIST->level();
    }

    public function verify(User $currentUser, User $userToVerify): bool
    {
        return $currentUser->canVerify($userToVerify);
    }

    private function isManagedArtist(User $manager, User $user): bool
    {
        return $manager->managedArtists()->whereKey($user->id)->exists();
    }
}
