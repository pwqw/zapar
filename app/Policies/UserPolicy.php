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

        // Managers can update their assigned artists or themselves
        if ($currentUser->role === Role::MANAGER) {
            // Can update themselves
            if ($currentUser->is($userToUpdate)) {
                return true;
            }

            // Can update their assigned artists
            return $currentUser->managedArtists()->whereKey($userToUpdate->id)->exists()
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
        // Cannot delete self
        if ($currentUser->is($userToDestroy)) {
            return false;
        }

        if ($currentUser->hasElevatedRole()) {
            return $currentUser->canManage($userToDestroy);
        }

        // Managers can delete their assigned artists
        if ($currentUser->role === Role::MANAGER) {
            return $currentUser->managedArtists()->whereKey($userToDestroy->id)->exists()
                && $currentUser->canManage($userToDestroy);
        }

        return false;
    }

    public function upload(User $currentUser): bool
    {
        // Artists and above can upload
        return $currentUser->role->level() >= Role::ARTIST->level();
    }

    public function verify(User $currentUser, User $userToVerify): bool
    {
        return $currentUser->canVerify($userToVerify);
    }
}
