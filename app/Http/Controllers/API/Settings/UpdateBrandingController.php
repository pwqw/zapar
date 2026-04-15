<?php

namespace App\Http\Controllers\API\Settings;

use App\Enums\Acl\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Settings\UpdateBrandingRequest;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class UpdateBrandingController extends Controller
{
    /** @param User $user */
    public function __construct(
        private readonly SettingService $settingService,
        private readonly Authenticatable $user,
    ) {}

    public function __invoke(UpdateBrandingRequest $request)
    {
        abort_unless($this->user->hasPermissionTo(Permission::MANAGE_SETTINGS), Response::HTTP_FORBIDDEN);

        $this->settingService->updateBranding(
            $request->name,
            $request->logo,
            $request->cover,
            $request->input('favicon'),
        );

        if ($request->has('description') || $request->has('og_image')) {
            $this->settingService->updateOpenGraph(
                $request->has('description') ? $request->string('description')->toString() : null,
                $request->has('og_image') ? $request->string('og_image')->toString() : null,
            );
        }

        return response()->noContent();
    }
}
