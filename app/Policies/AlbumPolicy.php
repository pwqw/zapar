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

    /**
     * If the user can update the album (e.g., edit name, year, or upload the cover image).
     * 
     * Rules follow the same logic as SongPolicy::edit():
     * - ADMIN and MODERATOR can edit ANY album (system-wide rule)
     * - Owner (artist) can edit their own albums
     * - Manager can edit albums if they can edit at least one song in the album
     * - Uploader can edit albums containing songs they uploaded
     */
    public function update(User $user, Album $album): bool
    {
        // Unknown albums are not editable.
        if ($album->is_unknown) {
            return false;
        }

        // ADMIN and MODERATOR can edit ANY album (system-wide rule)
        if ($user->hasElevatedRole()) {
            return true;
        }

        $artist = $album->artist;

        // Owner (artist) can edit their own albums
        if ($album->belongsToUser($user)) {
            return true;
        }

        // If album has no songs, only owner can edit (ADMIN/MODERATOR already handled above)
        if ($album->songs()->count() === 0) {
            return false;
        }

        // Check if user can edit at least one song in the album
        // This follows the same rules as SongPolicy::edit()
        $canEditAnySong = $album->songs()
            ->where(function ($query) use ($user) {
                // Owner can edit
                $query->where('owner_id', $user->id)
                    // Uploader can edit (if different from owner)
                    ->orWhere('uploaded_by_id', $user->id);
            })
            ->exists();

        // If no song found via simple queries, check manager permissions
        // This requires checking canEditArtistContent for each song
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

    /**
     * ADMIN and MODERATOR can manage encyclopedia data for any album; otherwise only the artist owner can.
     */
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
