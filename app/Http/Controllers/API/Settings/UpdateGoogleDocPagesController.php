<?php

namespace App\Http\Controllers\API\Settings;

use App\Enums\Acl\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Settings\UpdateGoogleDocPagesRequest;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class UpdateGoogleDocPagesController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly SettingService $settingService,
        private readonly Authenticatable $user,
    ) {
    }

    public function __invoke(UpdateGoogleDocPagesRequest $request): Response
    {
        abort_unless(
            $this->user->hasPermissionTo(Permission::MANAGE_SETTINGS),
            Response::HTTP_FORBIDDEN,
        );

        $this->settingService->updateGoogleDocPages($request->pages);

        return response()->noContent();
    }
}
