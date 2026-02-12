<?php

namespace App\Policies\Concerns;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ChecksArtistContentPermissions
{
    protected function canEditArtistSongs(User $user, Artist $artist, Builder $songsQuery): bool
    {
        $canEditAnySong = (clone $songsQuery)
            ->where(function (Builder $query) use ($user): void {
                $query->where('owner_id', $user->id)
                    ->orWhere('uploaded_by_id', $user->id);
            })
            ->exists();

        if ($canEditAnySong) {
            return true;
        }

        if ((clone $songsQuery)->whereNull('uploaded_by_id')->exists() && $user->canEditArtistContent($artist, null)) {
            return true;
        }

        /** @var array<int, int|string> $uploadedByIds */
        $uploadedByIds = (clone $songsQuery)
            ->whereNotNull('uploaded_by_id')
            ->distinct()
            ->pluck('uploaded_by_id')
            ->all();

        foreach ($uploadedByIds as $uploadedById) {
            if ($user->canEditArtistContent($artist, (int) $uploadedById)) {
                return true;
            }
        }

        return false;
    }
}
