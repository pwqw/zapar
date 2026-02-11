<?php

namespace App\Policies;

use App\Models\Artist;
use App\Models\User;

class ArtistPolicy
{
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

        $canEditAnySong = $artist->songs()
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhere('uploaded_by_id', $user->id);
            })
            ->exists();

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
