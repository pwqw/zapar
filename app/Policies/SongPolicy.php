<?php

namespace App\Policies;

use App\Enums\Acl\Role;
use App\Models\Song;
use App\Models\User;

class SongPolicy
{
    public function access(User $user, Song $song): bool
    {
        // Check if accessible via ownership or public visibility
        if ($song->accessibleBy($user)) {
            return true;
        }

        // If uploader is different from owner, allow access if user is the uploader or moderator
        if ($song->uploaded_by_id && $user->id === $song->uploaded_by_id) {
            return true;
        }

        // Moderators can access any song in their organization
        return
            $user->role->level() >= Role::MODERATOR->level()
            && $user->organization_id === $song->owner?->organization_id
        ;
    }

    public function own(User $user, Song $song): bool
    {
        return $song->ownedBy($user);
    }

    public function delete(User $user, Song $song): bool
    {
        // Owner can delete
        if ($song->ownedBy($user)) {
            return true;
        }

        // Manager can delete content of their managed artists (with restrictions)
        if ($song->owner && $user->canEditArtistContent($song->owner, $song->uploaded_by_id)) {
            return true;
        }

        // Moderator can delete in their organization
        if ($user->role === Role::MODERATOR && $user->organization_id === $song->owner?->organization_id) {
            return true;
        }

        // Admin can delete in their organization
        return $user->role === Role::ADMIN && $user->organization_id === $song->owner?->organization_id;
    }

    public function edit(User $user, Song $song): bool
    {
        // Owner can edit
        if ($song->ownedBy($user)) {
            return true;
        }

        // Manager can edit content of their managed artists (with restrictions)
        if ($song->owner && $user->canEditArtistContent($song->owner, $song->uploaded_by_id)) {
            return true;
        }

        // Uploader can edit (if different from owner and not covered by manager rules)
        if ($song->uploaded_by_id && $user->id === $song->uploaded_by_id) {
            return true;
        }

        // Moderator can edit in their organization
        if ($user->role === Role::MODERATOR && $user->organization_id === $song->owner?->organization_id) {
            return true;
        }

        // Admin can edit in their organization
        return $user->role === Role::ADMIN && $user->organization_id === $song->owner?->organization_id;
    }

    public function publish(User $user, Song $song): bool
    {
        // Only moderators and admins can publish
        if ($user->role === Role::MODERATOR || $user->role === Role::ADMIN) {
            return $user->organization_id === $song->owner?->organization_id || $user->role === Role::ADMIN;
        }

        return false;
    }

    public function download(User $user, Song $song): bool
    {
        return $this->access($user, $song);
    }
}
