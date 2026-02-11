<?php

namespace App\Policies;

use App\Enums\Acl\Role;
use App\Models\Artist;
use App\Models\User;

class ArtistPolicy
{
    public function access(User $user, Artist $artist): bool
    {
        return $artist->belongsToUser($user);
    }

    /**
     * Rules follow the same logic as SongPolicy::edit():
     * - ADMIN and MODERATOR can edit ANY artist (system-wide rule)
     * - Owner (artist) can edit their own artists
     * - Manager can edit artists if they can edit at least one song of the artist
     * - Uploader can edit artists if they uploaded songs of the artist
     */
    public function update(User $user, Artist $artist): bool
    {
        if ($artist->is_unknown || $artist->is_various) {
            return false;
        }

        // ADMIN and MODERATOR can edit ANY artist (system-wide rule)
        if ($user->role === Role::ADMIN || $user->role === Role::MODERATOR) {
            return true;
        }

        // Owner (artist) can edit their own artists
        if ($artist->belongsToUser($user)) {
            return true;
        }

        // Check if user can edit at least one song of the artist
        // This follows the same rules as SongPolicy::edit()
        $canEditAnySong = $artist->songs()
            ->where(function ($query) use ($user) {
                // Owner can edit
                $query->where('owner_id', $user->id)
                    // Uploader can edit (if different from owner)
                    ->orWhere('uploaded_by_id', $user->id);
            })
            ->exists();

        // If no song found via simple queries, check manager permissions
        if (!$canEditAnySong) {
            $canEditAnySong = $artist->songs()
                ->get()
                ->contains(function ($song) use ($user, $artist) {
                    return $user->canEditArtistContent($artist, $song->uploaded_by_id);
                });
        }

        return $canEditAnySong;
    }

    public function edit(User $user, Artist $artist): bool
    {
        return $this->update($user, $artist);
    }

    /**
     * ADMIN and MODERATOR can manage encyclopedia data for any artist; otherwise only the owner can.
     */
    public function fetchEncyclopedia(User $user, Artist $artist): bool
    {
        if ($artist->is_unknown || $artist->is_various) {
            return false;
        }

        if ($user->role === Role::ADMIN || $user->role === Role::MODERATOR) {
            return true;
        }

        return $artist->belongsToUser($user);
    }
}
