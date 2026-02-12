<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\User;
use App\Policies\Concerns\ChecksArtistContentPermissions;

class AlbumPolicy
{
    use ChecksArtistContentPermissions;

    public function access(User $user, Album $album): bool
    {
        return $album->belongsToUser($user);
    }

    public function update(User $user, Album $album): bool
    {
        if ($album->is_unknown) {
            return false;
        }

        if ($user->hasElevatedRole()) {
            return true;
        }

        if ($album->belongsToUser($user)) {
            return true;
        }

        if ($album->songs()->count() === 0) {
            return false;
        }

        if (!$album->artist) {
            return false;
        }

        return $this->canEditArtistSongs($user, $album->artist, $album->songs()->getQuery());
    }

    public function edit(User $user, Album $album): bool
    {
        return $this->update($user, $album);
    }

    public function fetchEncyclopedia(User $user, Album $album): bool
    {
        if ($album->is_unknown || !$album->artist) {
            return false;
        }

        if ($user->hasElevatedRole()) {
            return true;
        }

        return $album->artist->belongsToUser($user);
    }
}
