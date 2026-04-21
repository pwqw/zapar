<?php

namespace App\Repositories;

use App\Models\Podcast;
use App\Models\User;
use App\Repositories\Contracts\ScoutableRepository;
use Illuminate\Database\Eloquent\Collection;

/** @extends Repository<Podcast> */
class PodcastRepository extends Repository implements ScoutableRepository
{
    public function findOneByUrl(string $url): ?Podcast
    {
        return $this->findOneBy(['url' => $url]);
    }

    /** @return Collection<Podcast>|array<array-key, Podcast> */
    public function getAllSubscribedByUser(bool $favoritesOnly, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        $query = Podcast::query()
            ->with(['subscribers' => static fn ($query) => $query->where('users.id', $user->id)])
            ->setScopedUser($user)
            ->withFavoriteStatus(favoritesOnly: $favoritesOnly)
            ->accessible();

        // Upstream/release: solo suscritos. Fork Zapar: admin/moderator ven catálogo completo (público/privado por rol + org).
        if (!$user->isAdmin() && !$user->isModerator()) {
            $query->subscribed();
        }

        return $query->get();
    }

    /** @return Collection<Podcast>|array<array-key, Podcast> */
    public function getAllAccessibleByUser(bool $favoritesOnly, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        return Podcast::query()
            ->with(['subscribers' => static fn ($query) => $query->where('users.id', $user->id)])
            ->setScopedUser($user)
            ->withFavoriteStatus(favoritesOnly: $favoritesOnly)
            ->accessible()
            ->get();
    }

    /** @return Collection<Podcast>|array<array-key, Podcast> */
    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        $query = Podcast::query()
            ->with(['subscribers' => static fn ($query) => $query->where('users.id', $user->id)])
            ->setScopedUser($user)
            ->accessible()
            ->whereIn('podcasts.id', $ids);

        if (!$user->isAdmin() && !$user->isModerator()) {
            $query->subscribed();
        }

        $podcasts = $query->distinct()->get();

        return $preserveOrder ? $podcasts->orderByArray($ids) : $podcasts;
    }

    /** @return Collection<Podcast>|array<array-key, Podcast> */
    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        return $this->getMany(
            ids: Podcast::search($keywords)
                ->take($limit)
                ->get()
                ->modelKeys(),
            preserveOrder: true,
            user: $user,
        );
    }
}
