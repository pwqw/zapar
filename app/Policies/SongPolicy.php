<?php

namespace App\Policies;

use App\Models\Song;
use App\Models\User;

class SongPolicy
{
    public function access(User $user, Song $song): bool
    {
        return $song->accessibleBy($user);
    }

    public function own(User $user, Song $song): bool
    {
        return $song->ownedBy($user);
    }

    public function delete(User $user, Song $song): bool
    {
        return $song->editableBy($user);
    }

    public function edit(User $user, Song $song): bool
    {
        return $song->editableBy($user);
    }

    public function publish(User $user, Song $song): bool
    {
        if ($user->hasAdminOrModeratorRole()) {
            return true;
        }

        return $user->isVerified() && $song->ownedBy($user);
    }

    public function download(User $user, Song $song): bool
    {
        if (str_ends_with($user->email, '@' . User::ANONYMOUS_USER_DOMAIN)) {
            return false;
        }

        return $this->access($user, $song);
    }
}
