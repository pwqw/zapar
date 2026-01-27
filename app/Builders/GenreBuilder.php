<?php

namespace App\Builders;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GenreBuilder extends Builder
{
    public function accessibleBy(User $user): self
    {
        return $this->whereHas('songs', static fn (SongBuilder $query) => $query->setScopedUser($user)->accessible()); //@phpstan-ignore-line
    }
}
