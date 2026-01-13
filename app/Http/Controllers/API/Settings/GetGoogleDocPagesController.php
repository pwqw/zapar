<?php

namespace App\Http\Controllers\API\Settings;

use App\Enums\Acl\Permission;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class GetGoogleDocPagesController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly SettingService $settingService,
        private readonly Authenticatable $user,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        abort_unless(
            $this->user->hasPermissionTo(Permission::MANAGE_SETTINGS),
            JsonResponse::HTTP_FORBIDDEN,
        );

        $pages = $this->settingService->getGoogleDocPages();

        return response()->json(['pages' => $pages]);
    }
}
