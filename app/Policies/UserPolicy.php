<?php

namespace App\Policies;

use App\Enums\Acl\Permission;
use App\Enums\Acl\Role;
use App\Models\User;

class UserPolicy
{
    public function manage(User $currentUser): bool
    {
        // Admins can manage users globally
        if ($currentUser->hasPermissionTo(Permission::MANAGE_ALL_USERS)) {
            return true;
        }

        // Moderators can manage users in their organization
        if ($currentUser->hasPermissionTo(Permission::MANAGE_ORG_USERS)) {
            return true;
        }

        // Managers can manage their assigned artists
        if ($currentUser->hasPermissionTo(Permission::MANAGE_ARTISTS)) {
            return true;
        }

        return false;
    }

    public function update(User $currentUser, User $userToUpdate): bool
    {
        // Admins can update any user
        if ($currentUser->role === Role::ADMIN) {
            return $currentUser->canManage($userToUpdate);
        }

        // Moderators can update users in their organization
        if ($currentUser->role === Role::MODERATOR) {
            return $currentUser->organization_id === $userToUpdate->organization_id
                && $currentUser->canManage($userToUpdate);
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

        // Admins can delete any user
        if ($currentUser->role === Role::ADMIN) {
            return $currentUser->canManage($userToDestroy);
        }

        // Moderators can delete users in their organization
        if ($currentUser->role === Role::MODERATOR) {
            return $currentUser->organization_id === $userToDestroy->organization_id
                && $currentUser->canManage($userToDestroy);
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
