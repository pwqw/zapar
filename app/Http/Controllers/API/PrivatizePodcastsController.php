<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ChangeVisibilityRequest;
use App\Models\Podcast;
use App\Models\User;
use App\Services\PodcastService;
use Illuminate\Contracts\Auth\Authenticatable;

class PrivatizePodcastsController extends Controller
{
    /** @param User $user */
    public function __invoke(ChangeVisibilityRequest $request, PodcastService $podcastService, Authenticatable $user)
    {
        /** @var \Illuminate\Database\Eloquent\Collection<array-key, Podcast> $podcasts */
        $podcasts = Podcast::query()->findMany($request->podcasts);
        $podcasts->each(fn (Podcast $podcast) => $this->authorize('publish', $podcast));

        return response()->json($podcastService->markPodcastsAsPrivate($podcasts));
    }
}
