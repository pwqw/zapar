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

        return $song->accessibleBy($user)
            || ($song->uploaded_by_id && $user->id === $song->uploaded_by_id);
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

        return $song->ownedBy($user)
            || ($song->artist_user_id && $user->id === $song->artist_user_id)
            || ($song->uploaded_by_id && $user->id === $song->uploaded_by_id)
            || ($song->owner && $user->canEditArtistContent($song->owner, $song->uploaded_by_id));
    }

    public function edit(User $user, Song $song): bool
    {
        if ($user->hasElevatedRole()) {
            return true;
        }

        return $song->ownedBy($user)
            || ($song->artist_user_id && $user->id === $song->artist_user_id)
            || ($song->uploaded_by_id && $user->id === $song->uploaded_by_id)
            || ($song->owner && $user->canEditArtistContent($song->owner, $song->uploaded_by_id));
    }

    public function publish(User $user, Song $song): bool
    {
        if ($user->hasElevatedRole()) {
            return true;
        }

        return $user->isVerified()
            && ($song->ownedBy($user)
                || ($song->artist_user_id && $user->id === $song->artist_user_id)
                || ($song->uploaded_by_id && $user->id === $song->uploaded_by_id)
                || ($song->owner && $user->canEditArtistContent($song->owner, $song->uploaded_by_id)));
    }

    public function download(User $user, Song $song): bool
    {
        if (str_ends_with($user->email, '@' . User::ANONYMOUS_USER_DOMAIN)) {
            return false;
        }

        return $this->access($user, $song);
    }
}
