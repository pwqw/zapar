<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Settings\Concerns\AuthorizesManageSettings;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class GetGoogleDocPagesController extends Controller
{
    use AuthorizesManageSettings;

    /** @param User $user */
    public function __construct(
        private readonly SettingService $settingService,
        private readonly Authenticatable $user,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $this->authorizeManageSettings($this->user);

        $pages = $this->settingService->getGoogleDocPages();

        return response()->json(['pages' => $pages]);
    }
}
