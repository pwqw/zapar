<?php

namespace App\Builders;

use App\Builders\Concerns\CanScopeByUser;
use App\Enums\Acl\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use LogicException;

class PodcastBuilder extends FavoriteableBuilder
{
    use CanScopeByUser;

    public function subscribed(): self
    {
        throw_if(!$this->user, new LogicException('User must be set to query subscribed podcasts.'));

        return $this->join('podcast_user', function (JoinClause $join): void {
            $join->on('podcasts.id', 'podcast_user.podcast_id')
                ->where('podcast_user.user_id', $this->user->id);
        });
    }

    public function accessible(): self
    {
        throw_if(!$this->user, new LogicException('User must be set to query accessible podcasts.'));

        // Admins see ALL podcasts
        if ($this->user->role === Role::ADMIN) {
            return $this;
        }

        // Moderators see ALL podcasts (same as admin for visibility)
        if ($this->user->role === Role::MODERATOR) {
            return $this;
        }

        // Other users (manager, artist, user) see:
        // - Public podcasts from their organization
        // - Their own private podcasts
        return $this->where(function (self $query): void {
            // Public podcasts from users in the same organization
            $query->where(function (self $q): void {
                $q->where('podcasts.is_public', true)
                    ->whereExists(function ($subQuery): void {
                        $subQuery->select('users.id')
                            ->from('users')
                            ->whereColumn('users.id', 'podcasts.added_by')
                            ->where('users.organization_id', $this->user->organization_id);
                    });
            })
            // Or their own podcasts (public or private)
            ->orWhere('podcasts.added_by', $this->user->id);
        });
    }
}
