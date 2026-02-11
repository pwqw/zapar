<?php

namespace App\Policies;

use App\Models\Podcast;
use App\Models\User;

class PodcastPolicy
{
    public function access(User $user, Podcast $podcast): bool
    {
        if ($user->hasElevatedRole()) {
            return true;
        }

        return $podcast->added_by === $user->id
            || ($podcast->is_public && $this->isInUsersOrganization($user, $podcast));
    }

    public function view(User $user, Podcast $podcast): bool
    {
        return $this->access($user, $podcast);
    }

    public function edit(User $user, Podcast $podcast): bool
    {
        if ($user->hasElevatedRole()) {
            return true;
        }

        return $podcast->added_by === $user->id
            || ($user->isManager() && $this->isAddedByManagedArtist($user, $podcast));
    }

    public function update(User $user, Podcast $podcast): bool
    {
        return $this->edit($user, $podcast);
    }

    public function delete(User $user, Podcast $podcast): bool
    {
        return $this->edit($user, $podcast);
    }

    public function publish(User $user, Podcast $podcast): bool
    {
        if ($user->hasElevatedRole()) {
            return true;
        }

        return $user->isVerified()
            && ($podcast->added_by === $user->id
                || ($user->isManager() && $this->isAddedByManagedArtist($user, $podcast)));
    }

    private function isAddedByManagedArtist(User $user, Podcast $podcast): bool
    {
        if (!$podcast->added_by) {
            return false;
        }

        $addedByUser = User::find($podcast->added_by);

        return $addedByUser && $user->managedArtists()->whereKey($addedByUser->id)->exists();
    }

    private function isInUsersOrganization(User $user, Podcast $podcast): bool
    {
        if (!$podcast->added_by) {
            return false;
        }

        $addedByUser = User::find($podcast->added_by);

        return $addedByUser && $addedByUser->organization_id === $user->organization_id;
    }
}
