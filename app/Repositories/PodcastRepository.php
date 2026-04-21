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

    /**
     * Catálogo de podcasts para la app/web (índice).
     * Fork Zapar: todo usuario ve podcasts **accesibles** (`accessible()`): públicos de la organización
     * y los propios (público/privado), sin exigir fila en `podcast_user`. Los datos de suscripción
     * en JSON siguen viniendo del pivot cuando existe.
     *
     * @return Collection<Podcast>|array<array-key, Podcast>
     */
    public function getAllSubscribedByUser(bool $favoritesOnly, ?User $user = null): Collection
    {
        return $this->getAllAccessibleByUser($favoritesOnly, $user);
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

        $podcasts = Podcast::query()
            ->with(['subscribers' => static fn ($query) => $query->where('users.id', $user->id)])
            ->setScopedUser($user)
            ->accessible()
            ->whereIn('podcasts.id', $ids)
            ->distinct()
            ->get();

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
