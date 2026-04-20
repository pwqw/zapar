<?php

namespace App\Builders;

use App\Builders\Concerns\CanScopeByUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Webmozart\Assert\Assert;

class SongBuilder extends FavoriteableBuilder
{
    use CanScopeByUser;

    public const array SORT_COLUMNS_NORMALIZE_MAP = [
        'title' => 'songs.title',
        'track' => 'songs.track',
        'length' => 'songs.length',
        'created_at' => 'songs.created_at',
        'disc' => 'songs.disc',
        'year' => 'songs.year',
        'artist_name' => 'songs.artist_name',
        'album_name' => 'songs.album_name',
        'podcast_title' => 'podcasts.title',
        'podcast_author' => 'podcasts.author',
        'genre' => 'genres.name',
    ];

    private const array VALID_SORT_COLUMNS = [
        'songs.title',
        'songs.track',
        'songs.length',
        'songs.year',
        'songs.created_at',
        'songs.artist_name',
        'songs.album_name',
        'podcasts.title',
        'podcasts.author',
        'genres.name',
    ];

    public function inDirectory(string $path): self
    {
        // Make sure the path ends with a directory separator.
        $path = Str::finish(trim($path), DIRECTORY_SEPARATOR);

        return $this->where('path', 'LIKE', "$path%");
    }

    private function withPlayCount(): self
    {
        throw_unless($this->user, new LogicException('User must be set to query play counts.'));

        return $this->leftJoin('interactions', function (JoinClause $join): void {
            $join->on('interactions.song_id', 'songs.id')->where('interactions.user_id', $this->user->id);
        })->addSelect(DB::raw('COALESCE(interactions.play_count, 0) as play_count'));
    }

    public function accessible(): self
    {
        throw_unless($this->user, new LogicException('User must be set to query accessible songs.'));

        if ($this->user->isAdmin()) {
            return $this;
        }

        if ($this->user->isModerator()) {
            return $this->accessibleForModeratorInSameOrganization();
        }

        // We want to alias both podcasts and podcast_user tables to avoid possible conflicts with other joins.
        $this
            ->leftJoin('podcasts as podcasts_a11y', 'songs.podcast_id', 'podcasts_a11y.id')
            ->leftJoin('podcast_user as podcast_user_a11y', function (JoinClause $join): void {
                $join->on('podcasts_a11y.id', 'podcast_user_a11y.podcast_id')->where(
                    'podcast_user_a11y.user_id',
                    $this->user->id,
                );
            })
            ->whereNot(function (self $query): void {
                $organizationId = $this->user->organization_id;

                $query->whereNotNull('songs.podcast_id')
                    ->whereNull('podcast_user_a11y.podcast_id')
                    ->whereNot(static function (self $q) use ($organizationId): void {
                        $q->where('podcasts_a11y.is_public', true)
                            ->whereExists(static function ($sub) use ($organizationId): void {
                                $sub->selectRaw('1')
                                    ->from('users')
                                    ->whereColumn('users.id', 'podcasts_a11y.added_by')
                                    ->where('users.organization_id', $organizationId);
                            });
                    });
            });

        // If the song is a podcast episode, we need to ensure that the user has access to it.
        return $this->where(function (self $query): void {
            $query
                ->whereNotNull('songs.podcast_id')
                ->orWhere(function (self $q2) {
                    if (!$this->user->preferences->includePublicMedia) {
                        return $q2->whereBelongsTo($this->user, 'owner');
                    }

                    return $q2->where(function (self $q3): void {
                        $q3->whereBelongsTo($this->user, 'owner')->orWhere(function (self $q4): void {
                            $q4->where('songs.is_public', true)->whereHas('owner', fn (Builder $owner) => $owner->where(
                                'organization_id',
                                $this->user->organization_id,
                            )->where('users.id', '<>', $this->user->id));
                        });
                    });
                });
        });
    }

    /**
     * Moderators see all songs and episodes tied to their organization (owners / podcast curators in org).
     */
    private function accessibleForModeratorInSameOrganization(): self
    {
        $organizationId = $this->user->organization_id;

        $this
            ->leftJoin('podcasts as podcasts_a11y', 'songs.podcast_id', 'podcasts_a11y.id')
            ->leftJoin('podcast_user as podcast_user_a11y', function (JoinClause $join): void {
                $join->on('podcasts_a11y.id', 'podcast_user_a11y.podcast_id')->where(
                    'podcast_user_a11y.user_id',
                    $this->user->id,
                );
            });

        return $this->where(function (self $query) use ($organizationId): void {
            $query
                ->where(function (self $q) use ($organizationId): void {
                    $q->whereNull('songs.podcast_id')
                        ->whereExists(static function ($sub) use ($organizationId): void {
                            $sub->selectRaw('1')
                                ->from('users')
                                ->whereColumn('users.id', 'songs.owner_id')
                                ->where('users.organization_id', $organizationId);
                        });
                })
                ->orWhere(function (self $q) use ($organizationId): void {
                    $q->whereNotNull('songs.podcast_id')
                        ->where(function (self $q2) use ($organizationId): void {
                            $q2->whereNotNull('podcast_user_a11y.podcast_id')
                                ->orWhereExists(static function ($sub) use ($organizationId): void {
                                    $sub->selectRaw('1')
                                        ->from('users')
                                        ->whereColumn('users.id', 'podcasts_a11y.added_by')
                                        ->where('users.organization_id', $organizationId);
                                });
                        });
                });
        });
    }

    public function withUserContext(
        bool $includeFavoriteStatus = true,
        bool $favoritesOnly = false,
        bool $includePlayCount = true,
    ): self {
        return $this
            ->accessible()
            ->when($includeFavoriteStatus, static fn (self $query) => $query->withFavoriteStatus($favoritesOnly))
            ->when($includePlayCount, static fn (self $query) => $query->withPlayCount());
    }

    private function sortByOneColumn(string $column, string $direction): self
    {
        $column = self::normalizeSortColumn($column);

        Assert::oneOf($column, self::VALID_SORT_COLUMNS);
        Assert::oneOf(strtolower($direction), ['asc', 'desc']);

        return $this
            ->orderBy($column, $direction)
            // Depending on the column, we might need to order by other columns as well.
            ->when($column === 'songs.artist_name', static fn (self $query) => $query
                ->orderBy('songs.album_name')
                ->orderBy('songs.disc')
                ->orderBy('songs.track')
                ->orderBy('songs.title'))
            ->when($column === 'songs.album_name', static fn (self $query) => $query
                ->orderBy('songs.artist_name')
                ->orderBy('songs.disc')
                ->orderBy('songs.track')
                ->orderBy('songs.title'))
            ->when($column === 'track', static fn (self $query) => $query
                ->orderBy('songs.disc')
                ->orderBy('songs.track'));
    }

    public function sort(array $columns, string $direction): self
    {
        $this->when(
            in_array('podcast_title', $columns, true) || in_array('podcast_author', $columns, true),
            static fn (self $query) => $query->leftJoin('podcasts', 'songs.podcast_id', 'podcasts.id'),
        )->when(in_array('genre', $columns, true), static fn (self $query) => $query->leftJoin(
            'genre_song',
            'songs.id',
            'genre_song.song_id',
        )->leftJoin('genres', 'genre_song.genre_id', 'genres.id'));

        foreach ($columns as $column) {
            $this->sortByOneColumn($column, $direction);
        }

        return $this;
    }

    private static function normalizeSortColumn(string $column): string
    {
        return array_key_exists($column, self::SORT_COLUMNS_NORMALIZE_MAP)
            ? self::SORT_COLUMNS_NORMALIZE_MAP[$column]
            : $column;
    }

    public function storedOnCloud(): self
    {
        return $this->whereNotNull('storage')->where('storage', '!=', '')->whereNull('podcast_id');
    }

    public function storedLocally(): self
    {
        return $this->where(static function (self $query): void {
            $query->where(static function (self $q): void {
                $q->whereNull('songs.storage')->orWhere('songs.storage', '');
            })->whereNull('songs.podcast_id');
        });
    }
}
