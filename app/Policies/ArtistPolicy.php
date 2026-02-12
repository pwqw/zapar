<?php

namespace App\Policies;

use App\Models\Artist;
use App\Models\User;
use App\Policies\Concerns\ChecksArtistContentPermissions;

class ArtistPolicy
{
    use ChecksArtistContentPermissions;

    public function access(User $user, Artist $artist): bool
    {
        return $artist->belongsToUser($user);
    }

    public function update(User $user, Artist $artist): bool
    {
        if ($artist->is_unknown || $artist->is_various) {
            return false;
        }

        if ($user->hasElevatedRole()) {
            return true;
        }

        if ($artist->belongsToUser($user)) {
            return true;
        }

        return $this->canEditArtistSongs($user, $artist, $artist->songs()->getQuery());
    }

    public function edit(User $user, Artist $artist): bool
    {
        return $this->update($user, $artist);
    }

    public function fetchEncyclopedia(User $user, Artist $artist): bool
    {
        if ($artist->is_unknown || $artist->is_various) {
            return false;
        }

        if ($user->hasElevatedRole()) {
            return true;
        }

        return $artist->belongsToUser($user);
    }
}
