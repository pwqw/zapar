<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

class GoogleDocPageController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {
    }

    public function show(string $slug): JsonResponse
    {
        $page = $this->settingService->findGoogleDocPageBySlug($slug);

        if (!$page) {
            abort(JsonResponse::HTTP_NOT_FOUND, 'Google Doc page not found');
        }

        return response()->json($page);
    }
}
