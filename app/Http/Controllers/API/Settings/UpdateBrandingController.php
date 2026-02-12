<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Settings\Concerns\AuthorizesManageSettings;
use App\Http\Requests\API\Settings\UpdateBrandingRequest;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class UpdateBrandingController extends Controller
{
    use AuthorizesManageSettings;

    /** @param User $user */
    public function __construct(
        private readonly SettingService $settingService,
        private readonly Authenticatable $user,
    ) {
    }

    public function __invoke(UpdateBrandingRequest $request)
    {
        $this->authorizeManageSettings($this->user);

        $this->settingService->updateBranding(
            $request->name,
            $request->logo,
            $request->cover,
            $request->favicon,
        );

        // Update OpenGraph description and image if provided
        if ($request->description !== null || $request->og_image !== null) {
            $this->settingService->updateOpenGraph(
                $request->description,
                $request->og_image,
            );
        }

        return response()->noContent();
    }
}
