<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\AuthorizesVisibilityChanges;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ChangeVisibilityRequest;
use App\Models\Podcast;
use App\Models\Song;
use App\Models\User;
use App\Services\PodcastService;
use App\Services\SongService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ResourceVisibilityController extends Controller
{
    use AuthorizesVisibilityChanges;

    /** @param User $user */
    public function publicizeSongs(ChangeVisibilityRequest $request, SongService $songService, Authenticatable $user): Response
    {
        $songs = Song::query()->findMany($request->songs ?? []);
        $this->authorizeVisibilityChanges($songs, true, publishAbility: 'publish', privatizeAbility: 'edit');

        $songService->markSongsAsPublic($songs, $user);

        return response()->noContent();
    }

    /** @param User $user */
    public function privatizeSongs(ChangeVisibilityRequest $request, SongService $songService, Authenticatable $user): JsonResponse
    {
        $songs = Song::query()->findMany($request->songs ?? []);
        $this->authorizeVisibilityChanges($songs, false, publishAbility: 'publish', privatizeAbility: 'edit');

        return response()->json($songService->markSongsAsPrivate($songs, $user));
    }

    /** @param User $user */
    public function publicizePodcasts(
        ChangeVisibilityRequest $request,
        PodcastService $podcastService,
        Authenticatable $user,
    ): Response {
        $podcasts = Podcast::query()->findMany($request->podcasts ?? []);
        $this->authorizeVisibilityChanges($podcasts, true, publishAbility: 'publish', privatizeAbility: 'publish');

        $podcastService->markPodcastsAsPublic($podcasts, $user);

        return response()->noContent();
    }

    /** @param User $user */
    public function privatizePodcasts(
        ChangeVisibilityRequest $request,
        PodcastService $podcastService,
        Authenticatable $user,
    ): JsonResponse {
        $podcasts = Podcast::query()->findMany($request->podcasts ?? []);
        $this->authorizeVisibilityChanges($podcasts, false, publishAbility: 'publish', privatizeAbility: 'publish');

        return response()->json($podcastService->markPodcastsAsPrivate($podcasts, $user));
    }
}
