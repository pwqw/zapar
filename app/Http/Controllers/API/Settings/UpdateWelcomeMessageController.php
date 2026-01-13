<?php

namespace App\Http\Controllers\API\Settings;

use App\Enums\Acl\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Settings\UpdateWelcomeMessageRequest;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class UpdateWelcomeMessageController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly SettingService $settingService,
        private readonly Authenticatable $user,
    ) {
    }

    public function __invoke(UpdateWelcomeMessageRequest $request): Response
    {
        abort_unless(
            $this->user->hasPermissionTo(Permission::MANAGE_SETTINGS),
            Response::HTTP_FORBIDDEN,
        );

        $this->settingService->updateWelcomeMessage(
            $request->message,
            $request->variables ?? [],
        );

        return response()->noContent();
    }
}
