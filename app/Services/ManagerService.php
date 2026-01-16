<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class ManagerService
{
    public function assignArtist(User $manager, User $artist): void
    {
        if (!$manager->isManager()) {
            throw new InvalidArgumentException('User must be a manager');
        }

        $manager->managedArtists()->syncWithoutDetaching($artist->id);
    }

    public function removeArtist(User $manager, User $artist): void
    {
        $manager->managedArtists()->detach($artist->id);
    }

    public function getArtists(User $manager): Collection
    {
        return $manager->managedArtists()->get();
    }

    public function getManagers(User $artist): Collection
    {
        return $artist->managers()->get();
    }

    public function canManageArtist(User $manager, User $artist): bool
    {
        if (!$manager->isManager()) {
            return false;
        }

        return $manager->managedArtists()->whereKey($artist->id)->exists();
    }
}
