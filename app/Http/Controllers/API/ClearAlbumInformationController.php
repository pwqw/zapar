<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\EncyclopediaService;
use Illuminate\Http\JsonResponse;

class ClearAlbumInformationController extends Controller
{
    public function __invoke(Album $album, EncyclopediaService $encyclopediaService): JsonResponse
    {
        $this->authorize('fetchEncyclopedia', $album);

        $encyclopediaService->clearAlbumStoredData($album);

        return response()->json(null, 204);
    }
}
