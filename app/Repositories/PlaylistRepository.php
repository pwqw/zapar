<?php

namespace App\Repositories;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Support\Collection;

/** @extends Repository<Playlist> */
class PlaylistRepository extends Repository
{
    /** @return Collection<Playlist>|array<array-key, Playlist> */
    public function getAllAccessibleByUser(User $user): Collection
    {
        return $user->playlists()
            ->leftJoin('playlist_playlist_folder', 'playlists.id', '=', 'playlist_playlist_folder.playlist_id')
            ->distinct()
            ->get(['playlists.*', 'playlist_playlist_folder.folder_id']);
    }
}
