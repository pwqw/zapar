<?php

namespace App\Models\Concerns\Users;

use App\Enums\Acl\Role as RoleEnum;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasArtistManagerPermissions
{
    public function managedArtists(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'manager_artist', 'manager_id', 'artist_id')->withTimestamps();
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'manager_artist', 'artist_id', 'manager_id')->withTimestamps();
    }

    public function isManager(): bool
    {
        return $this->role === RoleEnum::MANAGER;
    }

    public function isArtist(): bool
    {
        return $this->role === RoleEnum::ARTIST;
    }

    public function canAssignCoOwnerArtist(): bool
    {
        return $this->role === RoleEnum::ADMIN || $this->role === RoleEnum::MODERATOR;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAssignableArtistsForCoOwner(): Collection
    {
        $query = User::query()->whereHas('roles', static function ($roles): void {
            $roles->where('name', RoleEnum::ARTIST->value);
        });

        if ($this->role === RoleEnum::MODERATOR) {
            $query->where('organization_id', $this->organization_id);
        }

        return $query->get();
    }

    public function canEditArtistContent(User|Artist $artist, ?int $uploadedById): bool
    {
        $artistUser = $artist instanceof Artist ? $artist->user : $artist;

        if (!$artistUser) {
            return false;
        }

        if ($this->id === $artistUser->id) {
            return true;
        }

        if (!$this->isManager()) {
            return false;
        }

        if (!$this->managedArtists()->whereKey($artistUser->id)->exists()) {
            return false;
        }

        if ($uploadedById === null) {
            return true;
        }

        if ($uploadedById === $artistUser->id) {
            return true;
        }

        if ($artistUser->managers()->count() === 1) {
            return true;
        }

        return $uploadedById === $this->id;
    }
}
