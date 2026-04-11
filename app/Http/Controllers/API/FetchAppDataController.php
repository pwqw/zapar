<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

class FetchAppDataController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'legal_urls' => $this->settingService->getConsentLegalUrls(),
        ]);
    }
}
