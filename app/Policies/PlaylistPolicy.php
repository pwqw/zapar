<?php

namespace App\Policies;

use App\Facades\License;
use App\Models\Playlist;
use App\Models\User;

class PlaylistPolicy
{
    public function create(User $user): bool
    {
        // Anonymous users cannot create playlists
        return !str_ends_with($user->email, '@' . User::ANONYMOUS_USER_DOMAIN);
    }

    public function access(User $user, Playlist $playlist): bool
    {
        return $this->own($user, $playlist) || $playlist->hasCollaborator($user);
    }

    public function own(User $user, Playlist $playlist): bool
    {
        return $playlist->ownedBy($user);
    }

    public function download(User $user, Playlist $playlist): bool
    {
        // Anonymous users cannot download
        if (str_ends_with($user->email, '@' . User::ANONYMOUS_USER_DOMAIN)) {
            return false;
        }

        return $this->access($user, $playlist);
    }

    public function inviteCollaborators(User $user, Playlist $playlist): bool
    {
        return License::isPlus() && $this->own($user, $playlist) && !$playlist->is_smart;
    }

    public function collaborate(User $user, Playlist $playlist): bool
    {
        return $this->own($user, $playlist) || $playlist->hasCollaborator($user);
    }
}
