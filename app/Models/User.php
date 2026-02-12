<?php

namespace App\Models;

use App\Builders\UserBuilder;
use App\Casts\UserPreferencesCast;
use App\Enums\Acl\Role as RoleEnum;
use App\Exceptions\UserAlreadySubscribedToPodcastException;
use App\Models\Contracts\Permissionable;
use App\Values\User\UserPreferences;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property ?Carbon $invitation_accepted_at
 * @property ?Carbon $invited_at
 * @property ?Carbon $terms_accepted_at
 * @property ?Carbon $privacy_accepted_at
 * @property ?Carbon $age_verified_at
 * @property ?User $invitedBy
 * @property ?string $invitation_token
 * @property Collection<array-key, UserConsentLog> $consentLogs
 * @property Collection<array-key, Playlist> $collaboratedPlaylists
 * @property Collection<array-key, Playlist> $playlists
 * @property Collection<array-key, PlaylistFolder> $playlistFolders
 * @property Collection<array-key, Podcast> $podcasts
 * @property Collection<array-key, Theme> $themes
 * @property Organization $organization
 * @property PersonalAccessToken $currentAccessToken
 * @property UserPreferences $preferences
 * @property int $id
 * @property string $email
 * @property string $name
 * @property string $organization_id
 * @property string $password
 * @property string $public_id
 * @property bool $verified
 * @property-read ?string $sso_id
 * @property-read ?string $sso_provider
 * @property-read bool $connected_to_lastfm Whether the user is connected to Last.fm
 * @property-read bool $has_custom_avatar
 * @property-read bool $is_prospect
 * @property-read bool $is_sso
 * @property-read string $avatar
 * @property-read RoleEnum $role
 */
class User extends Authenticatable implements AuditableContract, Permissionable
{
    use Auditable;
    use HasApiTokens;
    use HasFactory;
    use HasRoles {
        scopeRole as scopeWhereRole;
    }
    use Notifiable;
    use Prunable;

    private const FIRST_ADMIN_NAME = 'Koel';
    public const FIRST_ADMIN_EMAIL = 'admin@koel.dev';
    public const FIRST_ADMIN_PASSWORD = 'KoelIsCool';
    public const DEMO_PASSWORD = 'demo';
    public const DEMO_USER_DOMAIN = 'demo.koel.dev';
    public const ANONYMOUS_USER_DOMAIN = 'sin.email';
    public const ANONYMOUS_PASSWORD = 'anonymous';

    protected $guarded = ['id', 'public_id'];
    protected $hidden = ['password', 'remember_token', 'created_at', 'updated_at', 'invitation_accepted_at'];
    protected $appends = ['avatar'];
    protected array $auditExclude = ['password', 'remember_token', 'invitation_token'];
    protected $with = ['roles', 'permissions'];

    protected function casts(): array
    {
        return [
            'preferences' => UserPreferencesCast::class,
            'verified' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'age_verified_at' => 'datetime',
        ];
    }

    public static function query(): UserBuilder
    {
        /** @var UserBuilder */
        return parent::query();
    }

    public function newEloquentBuilder($query): UserBuilder
    {
        return new UserBuilder($query);
    }

    /**
     * The first admin user in the system.
     * This user is created automatically if it does not exist (e.g., during installation or unit tests).
     */
    public static function firstAdmin(): static
    {
        $defaultOrganization = Organization::default();

        return static::query() // @phpstan-ignore-line
            ->whereRole(RoleEnum::ADMIN)
            ->where('organization_id', $defaultOrganization->id)
            ->oldest()
            ->firstOr(static function () use ($defaultOrganization): User {
                /** @var User $user */
                $user = static::query()->create([
                    'email' => self::FIRST_ADMIN_EMAIL,
                    'name' => self::FIRST_ADMIN_NAME,
                    'password' => Hash::make(self::FIRST_ADMIN_PASSWORD),
                    'organization_id' => $defaultOrganization->id,
                ]);

                return $user->syncRoles(RoleEnum::ADMIN);
            });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'invited_by_id');
    }

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class)
            ->withPivot('role', 'position')
            ->withTimestamps();
    }

    public function ownedPlaylists(): BelongsToMany
    {
        return $this->playlists()->wherePivot('role', 'owner');
    }

    public function collaboratedPlaylists(): BelongsToMany
    {
        return $this->playlists()->wherePivot('role', 'collaborator');
    }

    public function playlistFolders(): HasMany
    {
        return $this->hasMany(PlaylistFolder::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function podcasts(): BelongsToMany
    {
        return $this->belongsToMany(Podcast::class)
            ->using(PodcastUserPivot::class)
            ->withTimestamps();
    }

    public function radioStations(): HasMany
    {
        return $this->hasMany(RadioStation::class);
    }

    public function managedArtists(): BelongsToMany
    {
        return $this->belongsToMany(
            __CLASS__,
            'manager_artist',
            'manager_id',
            'artist_id'
        )->withTimestamps();
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(
            __CLASS__,
            'manager_artist',
            'artist_id',
            'manager_id'
        )->withTimestamps();
    }

    public function themes(): HasMany
    {
        return $this->hasMany(Theme::class);
    }

    public function consentLogs(): HasMany
    {
        return $this->hasMany(UserConsentLog::class);
    }

    public function subscribedToPodcast(Podcast $podcast): bool
    {
        return $this->podcasts()->whereKey($podcast)->exists();
    }

    public function subscribeToPodcast(Podcast $podcast): void
    {
        throw_if(
            $this->subscribedToPodcast($podcast),
            UserAlreadySubscribedToPodcastException::create($this, $podcast)
        );

        $this->podcasts()->attach($podcast);
    }

    public function unsubscribeFromPodcast(Podcast $podcast): void
    {
        $this->podcasts()->detach($podcast);
    }

    protected function avatar(): Attribute
    {
        return Attribute::get(fn (): string => avatar_or_gravatar(Arr::get($this->attributes, 'avatar'), $this->email))
            ->shouldCache();
    }

    protected function hasCustomAvatar(): Attribute
    {
        return Attribute::get(fn () => (bool)$this->getRawOriginal('avatar'))->shouldCache();
    }

    protected function isProspect(): Attribute
    {
        return Attribute::get(fn (): bool => (bool)$this->invitation_token);
    }

    protected function isSso(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) $this->sso_provider)->shouldCache();
    }

    protected function connectedToLastfm(): Attribute
    {
        return Attribute::get(fn (): bool => (bool)$this->preferences->lastFmSessionKey)->shouldCache();
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** Delete all old and inactive demo and anonymous users */
    public function prunable(): Builder
    {
        $query = static::query();
        $demoQuery = null;
        $anonymousQuery = null;

        if (config('koel.misc.demo')) {
            $demoQuery = static::query()
                ->where('created_at', '<=', now()->subWeek())
                ->where('email', 'like', '%@' . self::DEMO_USER_DOMAIN)
                ->whereDoesntHave('interactions', static function (Builder $q): void {
                    $q->where('last_played_at', '>=', now()->subDays(7));
                });
        }

        if (config('koel.misc.allow_anonymous')) {
            $anonymousQuery = static::query()
                ->where('email', 'like', '%@' . self::ANONYMOUS_USER_DOMAIN)
                ->where('updated_at', '<=', now()->subDays(2))
                ->whereDoesntHave('interactions', static function (Builder $q): void {
                    $q->where('last_played_at', '>=', now()->subDays(2));
                });
        }

        if ($demoQuery && $anonymousQuery) {
            return $query->where(static function (Builder $q) use ($demoQuery, $anonymousQuery): void {
                $q->whereIn('id', $demoQuery->select('id'))
                    ->orWhereIn('id', $anonymousQuery->select('id'));
            });
        }

        if ($demoQuery) {
            return $demoQuery;
        }

        if ($anonymousQuery) {
            return $anonymousQuery;
        }

        return static::query()->whereRaw('false');
    }

    protected function role(): Attribute
    {
        // Enforce a single-role permission model
        return Attribute::make(
            get: function () {
                $role = $this->getRoleNames();

                if ($role->isEmpty()) {
                    return RoleEnum::default();
                }

                return RoleEnum::tryFrom($role->sole()) ?? RoleEnum::default();
            },
        );
    }

    public function isArtist(): bool
    {
        return $this->role === RoleEnum::ARTIST;
    }

    public function isManager(): bool
    {
        return $this->role === RoleEnum::MANAGER;
    }

    public function isModerator(): bool
    {
        return $this->role === RoleEnum::MODERATOR;
    }

    public function isAdmin(): bool
    {
        return $this->role === RoleEnum::ADMIN;
    }

    public function hasElevatedRole(): bool
    {
        return $this->role->level() >= RoleEnum::MODERATOR->level();
    }

    /**
     * Whether this user can assign a co-owner (artist) to songs in the edit form.
     * Admin, moderator, and manager can—each with different artist visibility.
     */
    public function canAssignCoOwnerArtist(): bool
    {
        return $this->isAdmin() || $this->isModerator() || $this->isManager();
    }

    /**
     * Users (artist role) this user can assign as song co-owner.
     * Admin: all artists; Moderator: artists in same org; Manager: managed artists only.
     *
     * @return \Illuminate\Support\Collection<int, array{id: string, name: string}>
     */
    public function getAssignableArtistsForCoOwner(): \Illuminate\Support\Collection
    {
        if ($this->isAdmin()) {
            return static::query()
                ->whereRole(RoleEnum::ARTIST)
                ->orderBy('name')
                ->get()
                ->map(fn (User $u) => ['id' => $u->public_id, 'name' => $u->name]);
        }

        if ($this->isModerator()) {
            return static::query()
                ->whereRole(RoleEnum::ARTIST)
                ->where('organization_id', $this->organization_id)
                ->orderBy('name')
                ->get()
                ->map(fn (User $u) => ['id' => $u->public_id, 'name' => $u->name]);
        }

        if ($this->isManager()) {
            return $this->managedArtists
                ->sortBy('name')
                ->map(fn (User $u) => ['id' => $u->public_id, 'name' => $u->name])
                ->values();
        }

        return collect();
    }

    /**
     * Whether the given artist user (by public_id) can be assigned as co-owner by this user.
     */
    public function canAssignArtistAsCoOwner(?string $artistUserPublicId): bool
    {
        if ($artistUserPublicId === null || $artistUserPublicId === '') {
            return true;
        }

        $assignableIds = $this->getAssignableArtistsForCoOwner()->pluck('id')->all();

        return in_array($artistUserPublicId, $assignableIds, true);
    }

    public function canUploadAs(User $artist): bool
    {
        // User can upload as themselves or as an artist they manage
        if ($this->id === $artist->id) {
            return true;
        }

        if ($this->hasElevatedRole()) {
            return true;
        }

        // Manager can upload as any of their managed artists
        if ($this->isManager()) {
            return $this->managedArtists()->whereKey($artist->id)->exists();
        }

        return false;
    }

    public function canManage(User $other): bool
    {
        return $this->role->canManage($other->role);
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    /**
     * Check if this user can verify another user.
     *
     * - Elevated roles (ADMIN, MODERATOR) can verify any user they can manage
     * - Manager (verified) can verify their assigned artists
     * - Manager (not verified) cannot verify anyone
     */
    public function canVerify(User $other): bool
    {
        if ($this->hasElevatedRole()) {
            return $this->canManage($other);
        }

        // Manager must be verified to verify others
        if ($this->isManager()) {
            if (!$this->isVerified()) {
                return false;
            }

            // Can only verify their assigned artists
            return $other->isArtist() && $this->managedArtists()->whereKey($other->id)->exists();
        }

        return false;
    }

    /**
     * Check if this manager can edit content belonging to an artist.
     *
     * Rules:
     * - If artist has only 1 manager: that manager can edit ALL content
     * - If artist has 2+ managers: each manager can only edit:
     *   - Content they uploaded themselves (uploaded_by_id = manager.id)
     *   - Content the artist uploaded themselves (uploaded_by_id = artist.id)
     *   - But NOT content uploaded by other managers
     *
     * @param User $artist The artist who owns the content
     * @param int|null $uploadedById The ID of who uploaded the content
     * @return bool
     */
    public function canEditArtistContent(User $artist, ?int $uploadedById): bool
    {
        // Not a manager relationship? No access
        if (!$this->isManager() || !$this->managedArtists()->whereKey($artist->id)->exists()) {
            return false;
        }

        // If content has no uploader info, allow edit (legacy content)
        if ($uploadedById === null) {
            return true;
        }

        // Manager can always edit content they uploaded themselves
        if ($uploadedById === $this->id) {
            return true;
        }

        // Manager can always edit content the artist uploaded themselves
        if ($uploadedById === $artist->id) {
            return true;
        }

        // Count how many managers this artist has
        $managerCount = $artist->managers()->count();

        // If artist has only 1 manager, that manager can edit everything
        if ($managerCount === 1) {
            return true;
        }

        // Artist has 2+ managers: can't edit content uploaded by other managers
        return false;
    }

    public static function getPermissionableIdentifier(): string
    {
        return 'public_id';
    }
}
