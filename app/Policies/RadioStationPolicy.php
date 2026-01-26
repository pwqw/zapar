<?php

namespace App\Policies;

use App\Enums\Acl\Role;
use App\Models\RadioStation;
use App\Models\User;

class RadioStationPolicy
{
    public function access(User $user, RadioStation $station): bool
    {
        // Owner can access
        if ($station->user_id === $user->id) {
            return true;
        }

        // Public stations are accessible
        if ($station->is_public) {
            return true;
        }

        // If uploader is different from owner, allow access if user is the uploader
        if ($station->uploaded_by_id && $user->id === $station->uploaded_by_id) {
            return true;
        }

        // Moderators can access any station in their organization
        return
            $user->role->level() >= Role::MODERATOR->level()
            && $user->organization_id === $station->user->organization_id
        ;
    }

    public function edit(User $user, RadioStation $station): bool
    {
        // ADMIN and MODERATOR can edit ANY radio station (system-wide rule)
        if ($user->role === Role::ADMIN || $user->role === Role::MODERATOR) {
            return true;
        }

        // Owner can edit
        if ($station->user_id === $user->id) {
            return true;
        }

        // Manager can edit content of their managed artists (with restrictions)
        if ($station->user && $user->canEditArtistContent($station->user, $station->uploaded_by_id)) {
            return true;
        }

        // Uploader can edit (if different from owner and not covered by manager rules)
        if ($station->uploaded_by_id && $user->id === $station->uploaded_by_id) {
            return true;
        }

        return false;
    }

    public function update(User $user, RadioStation $station): bool
    {
        return $this->edit($user, $station);
    }

    public function delete(User $user, RadioStation $station): bool
    {
        return $this->edit($user, $station);
    }

    public function publish(User $user, RadioStation $station): bool
    {
        // Admin can always publish
        if ($user->role === Role::ADMIN) {
            return true;
        }

        // Moderator can publish in their organization
        if ($user->role === Role::MODERATOR) {
            return $user->organization_id === $station->user->organization_id;
        }

        // Verified users can publish their own stations
        if ($user->isVerified() && $station->user_id === $user->id) {
            return true;
        }

        // Verified users can publish stations of their managed artists
        if ($user->isVerified() && $station->user && $user->canEditArtistContent($station->user, $station->uploaded_by_id)) {
            return true;
        }

        return false;
    }
}
