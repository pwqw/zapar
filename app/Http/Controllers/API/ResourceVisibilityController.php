<?php

namespace App\Http\Controllers\API;

use App\Facades\License;
use App\Http\Controllers\API\Concerns\AuthorizesVisibilityChanges;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ChangeVisibilityRequest;
use App\Models\Podcast;
use App\Models\Song;
use App\Services\PodcastService;
use App\Services\SongService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ResourceVisibilityController extends Controller
{
    use AuthorizesVisibilityChanges;

    public function publicizeSongs(ChangeVisibilityRequest $request, SongService $songService): Response
    {
        abort_if(License::isCommunity(), Response::HTTP_NOT_FOUND);

        $songs = Song::query()->findMany($request->songs ?? []);
        $this->authorizeVisibilityChanges($songs, true, publishAbility: 'publish', privatizeAbility: 'edit');

        $songService->markSongsAsPublic($songs);

        return response()->noContent();
    }

    public function privatizeSongs(ChangeVisibilityRequest $request, SongService $songService): JsonResponse
    {
        abort_if(License::isCommunity(), Response::HTTP_NOT_FOUND);

        $songs = Song::query()->findMany($request->songs ?? []);
        $this->authorizeVisibilityChanges($songs, false, publishAbility: 'publish', privatizeAbility: 'edit');

        return response()->json($songService->markSongsAsPrivate($songs));
    }

    /** @param User $user */
    public function publicizePodcasts(
        ChangeVisibilityRequest $request,
        PodcastService $podcastService,
    ): Response {
        $podcasts = Podcast::query()->findMany($request->podcasts ?? []);
        $this->authorizeVisibilityChanges($podcasts, true, publishAbility: 'publish', privatizeAbility: 'publish');

        $podcastService->markPodcastsAsPublic($podcasts);

        return response()->noContent();
    }

    /** @param User $user */
    public function privatizePodcasts(
        ChangeVisibilityRequest $request,
        PodcastService $podcastService,
    ): JsonResponse {
        $podcasts = Podcast::query()->findMany($request->podcasts ?? []);
        $this->authorizeVisibilityChanges($podcasts, false, publishAbility: 'publish', privatizeAbility: 'publish');

        return response()->json($podcastService->markPodcastsAsPrivate($podcasts));
    }
}
