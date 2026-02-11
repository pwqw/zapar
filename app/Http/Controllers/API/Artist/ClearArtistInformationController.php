<?php

namespace App\Http\Controllers\API\Artist;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Services\EncyclopediaService;
use Illuminate\Http\JsonResponse;

class ClearArtistInformationController extends Controller
{
    public function __invoke(Artist $artist, EncyclopediaService $encyclopediaService): JsonResponse
    {
        $this->authorize('fetchEncyclopedia', $artist);

        $encyclopediaService->clearArtistStoredData($artist);

        return response()->json(null, 204);
    }
}
