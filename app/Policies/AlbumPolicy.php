<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\User;

class AlbumPolicy
{
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

        $artist = $album->artist;

        if ($album->belongsToUser($user)) {
            return true;
        }

        if ($album->songs()->count() === 0) {
            return false;
        }

        $canEditAnySong = $album->songs()
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhere('uploaded_by_id', $user->id);
            })
            ->exists();

        if (!$canEditAnySong && $artist) {
            $canEditAnySong = $album->songs()
                ->get()
                ->contains(function ($song) use ($user, $artist) {
                    return $user->canEditArtistContent($artist, $song->uploaded_by_id);
                });
        }

        return $canEditAnySong;
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
