<?php

namespace App\Policies;

use App\Enums\Acl\Role;
use App\Models\Podcast;
use App\Models\User;

class PodcastPolicy
{
    public function access(User $user, Podcast $podcast): bool
    {
        return $user->subscribedToPodcast($podcast);
    }

    public function view(User $user, Podcast $podcast): bool
    {
        return $user->subscribedToPodcast($podcast);
    }

    public function edit(User $user, Podcast $podcast): bool
    {
        // User who added the podcast can edit
        if ($podcast->added_by === $user->id) {
            return true;
        }

        // Manager can edit podcasts added by their managed artists
        if ($user->isManager() && $podcast->added_by) {
            $addedByUser = User::find($podcast->added_by);
            if ($addedByUser && $user->managedArtists()->whereKey($addedByUser->id)->exists()) {
                return true;
            }
        }

        // Moderator can edit in their organization
        if ($user->role === Role::MODERATOR && $podcast->added_by) {
            $addedByUser = User::find($podcast->added_by);
            if ($addedByUser && $user->organization_id === $addedByUser->organization_id) {
                return true;
            }
        }

        // Admin can edit in their organization
        if ($user->role === Role::ADMIN && $podcast->added_by) {
            $addedByUser = User::find($podcast->added_by);
            return $addedByUser && $user->organization_id === $addedByUser->organization_id;
        }

        return false;
    }

    public function update(User $user, Podcast $podcast): bool
    {
        return $this->edit($user, $podcast);
    }

    public function delete(User $user, Podcast $podcast): bool
    {
        return $this->edit($user, $podcast);
    }
}
