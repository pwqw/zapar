<?php

namespace App\Builders\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait CanScopeByUser
{
    protected ?User $user = null;

    public function setScopedUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    protected function scopeToSameOrganizationExceptCurrentUser(Builder $ownerQuery): void
    {
        throw_unless($this->user, new \LogicException('User must be set to scope organization filters.'));

        $ownerQuery->where('organization_id', $this->user->organization_id)
            ->where('owner_id', '<>', $this->user->id);
    }
}
