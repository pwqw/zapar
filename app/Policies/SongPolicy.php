<?php

namespace App\Policies;

use App\Models\Song;
use App\Models\User;

class SongPolicy
{
    public function access(User $user, Song $song): bool
    {
        if ($user->hasElevatedRole()) {
            return true;
        }

        // Check if accessible via ownership or public visibility
        if ($song->accessibleBy($user)) {
            return true;
        }

        // If uploader is different from owner, allow access if user is the uploader or moderator
        if ($song->uploaded_by_id && $user->id === $song->uploaded_by_id) {
            return true;
        }

        return false;
    }

    public function own(User $user, Song $song): bool
    {
        return $song->ownedBy($user);
    }

    public function delete(User $user, Song $song): bool
    {
        if ($user->hasElevatedRole()) {
            return true;
        }

        // Owner can delete
        if ($song->ownedBy($user)) {
            return true;
        }

        // Manager can delete content of their managed artists (with restrictions)
        if ($song->owner && $user->canEditArtistContent($song->owner, $song->uploaded_by_id)) {
            return true;
        }

        return false;
    }

    public function edit(User $user, Song $song): bool
    {
        if ($user->hasElevatedRole()) {
            return true;
        }

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

        return false;
    }

    public function publish(User $user, Song $song): bool
    {
        if ($user->hasElevatedRole()) {
            return true;
        }

        // Verified users can publish their own songs
        if ($user->isVerified() && $song->ownedBy($user)) {
            return true;
        }

        // Verified users can publish songs of their managed artists
        if ($user->isVerified() && $song->owner && $user->canEditArtistContent($song->owner, $song->uploaded_by_id)) {
            return true;
        }

        return false;
    }

    public function download(User $user, Song $song): bool
    {
        // Anonymous users cannot download
        if (str_ends_with($user->email, '@' . User::ANONYMOUS_USER_DOMAIN)) {
            return false;
        }

        return $this->access($user, $song);
    }
}
