<?php

namespace App\Http\Controllers\API;

use App\Enums\Acl\Role;
use App\Exceptions\UserProspectUpdateDeniedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\UserStoreRequest;
use App\Http\Requests\API\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    public function index()
    {
        $this->authorize('manage', User::class);

        $currentUser = auth()->user();

        if ($currentUser->hasElevatedRole()) {
            $users = User::with('managedArtists')->get();
        } elseif ($currentUser->isManager()) {
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

    public function listManagedArtists(User $manager)
    {
        $currentUser = auth()->user();

        if (!$this->canManageArtistsForManager($currentUser, $manager)) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot view this manager\'s artists.');
        }

        return UserResource::collection($manager->managedArtists);
    }

    public function assignArtist(User $manager, User $artist)
    {
        $currentUser = auth()->user();

        if (!$this->canManageArtistsForManager($currentUser, $manager)) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot assign artists to this manager.');
        }

        if ($artist->role !== Role::ARTIST) {
            abort(Response::HTTP_BAD_REQUEST, 'The user must have the artist role.');
        }

        if ($manager->organization_id !== $artist->organization_id) {
            abort(Response::HTTP_BAD_REQUEST, 'Manager and artist must be in the same organization.');
        }

        if (!$manager->managedArtists()->where('artist_id', $artist->id)->exists()) {
            $manager->managedArtists()->attach($artist->id);
        }

        return response()->json(['message' => 'Artist assigned successfully.']);
    }

    public function removeArtist(User $manager, User $artist)
    {
        $currentUser = auth()->user();

        if (!$this->canManageArtistsForManager($currentUser, $manager)) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot remove artists from this manager.');
        }

        $manager->managedArtists()->detach($artist->id);

        return response()->json(['message' => 'Artist removed successfully.']);
    }

    private function canManageArtistsForManager(User $currentUser, User $manager): bool
    {
        return $currentUser->is($manager) || $currentUser->hasElevatedRole();
    }
}
