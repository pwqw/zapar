<?php

namespace App\Policies;

use App\Models\RadioStation;
use App\Models\User;

class RadioStationPolicy
{
    public function access(User $user, RadioStation $station): bool
    {
        if ($user->hasAdminOrModeratorRole()) {
            return $this->radioStationBelongsToUserOrganization($user, $station);
        }

        return $station->user_id === $user->id
            || $station->is_public
            || ($station->uploaded_by_id && $user->id === $station->uploaded_by_id);
    }

    public function edit(User $user, RadioStation $station): bool
    {
        if ($user->hasAdminOrModeratorRole()) {
            return $this->radioStationBelongsToUserOrganization($user, $station);
        }

        return $station->user_id === $user->id
            || ($station->user && $user->canEditArtistContent($station->user, $station->uploaded_by_id))
            || ($station->uploaded_by_id && $user->id === $station->uploaded_by_id);
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
        if ($user->hasAdminOrModeratorRole()) {
            return $this->radioStationBelongsToUserOrganization($user, $station);
        }

        return $user->isVerified()
            && ($station->user_id === $user->id
                || ($station->user && $user->canEditArtistContent($station->user, $station->uploaded_by_id)));
    }

    private function radioStationBelongsToUserOrganization(User $user, RadioStation $station): bool
    {
        $owner = $station->relationLoaded('user') ? $station->user : User::query()->find($station->user_id);

        return $owner && $owner->organization_id === $user->organization_id;
    }
}
