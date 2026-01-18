<?php

namespace App\Http\Controllers\API;

use App\Enums\Acl\Permission;
use App\Exceptions\UserProspectUpdateDeniedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\UserStoreRequest;
use App\Http\Requests\API\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserService $userService,
    ) {
    }

    public function index()
    {
        $this->authorize('manage', User::class);

        $currentUser = auth()->user();

        // Admins can see all users
        if ($currentUser->hasPermissionTo(Permission::MANAGE_ALL_USERS)) {
            $users = User::with('managedArtists')->get();
        }
        // Moderators can see users in their organization
        elseif ($currentUser->hasPermissionTo(Permission::MANAGE_ORG_USERS)) {
            $users = User::with('managedArtists')
                ->where('organization_id', $currentUser->organization_id)
                ->get();
        }
        // Managers can only see their assigned artists
        elseif ($currentUser->hasPermissionTo(Permission::MANAGE_ARTISTS)) {
            $users = $currentUser->managedArtists;
        } else {
            $users = collect();
        }

        return UserResource::collection($users);
    }

    public function store(UserStoreRequest $request)
    {
        $this->authorize('manage', User::class);

        return UserResource::make($this->userService->createUser($request->toDto()));
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $this->authorize('update', $user);

        try {
            return UserResource::make($this->userService->updateUser($user, $request->toDto()));
        } catch (UserProspectUpdateDeniedException) {
            abort(Response::HTTP_FORBIDDEN, 'Cannot update a user prospect.');
        }
    }

    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);
        $this->userService->deleteUser($user);

        return response()->noContent();
    }

    /**
     * List all artists managed by a specific manager.
     */
    public function listManagedArtists(User $manager)
    {
        $currentUser = auth()->user();

        // Only the manager themselves, moderators, or admins can list managed artists
        if (
            !$currentUser->is($manager) &&
            !$currentUser->hasPermissionTo(Permission::MANAGE_ORG_USERS) &&
            !$currentUser->hasPermissionTo(Permission::MANAGE_ALL_USERS)
        ) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot view this manager\'s artists.');
        }

        return UserResource::collection($manager->managedArtists);
    }

    /**
     * Assign an artist to a manager.
     */
    public function assignArtist(User $manager, User $artist)
    {
        $currentUser = auth()->user();

        // Only the manager themselves, moderators, or admins can assign artists
        if (
            !$currentUser->is($manager) &&
            !$currentUser->hasPermissionTo(Permission::MANAGE_ORG_USERS) &&
            !$currentUser->hasPermissionTo(Permission::MANAGE_ALL_USERS)
        ) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot assign artists to this manager.');
        }

        // Verify the artist is actually an artist role
        if ($artist->role->value !== 'artist') {
            abort(Response::HTTP_BAD_REQUEST, 'The user must have the artist role.');
        }

        // Verify both users are in the same organization
        if ($manager->organization_id !== $artist->organization_id) {
            abort(Response::HTTP_BAD_REQUEST, 'Manager and artist must be in the same organization.');
        }

        // Attach if not already attached
        if (!$manager->managedArtists()->where('artist_id', $artist->id)->exists()) {
            $manager->managedArtists()->attach($artist->id);
        }

        return response()->json(['message' => 'Artist assigned successfully.']);
    }

    /**
     * Remove an artist from a manager.
     */
    public function removeArtist(User $manager, User $artist)
    {
        $currentUser = auth()->user();

        // Only the manager themselves, moderators, or admins can remove artists
        if (
            !$currentUser->is($manager) &&
            !$currentUser->hasPermissionTo(Permission::MANAGE_ORG_USERS) &&
            !$currentUser->hasPermissionTo(Permission::MANAGE_ALL_USERS)
        ) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot remove artists from this manager.');
        }

        $manager->managedArtists()->detach($artist->id);

        return response()->json(['message' => 'Artist removed successfully.']);
    }
}
