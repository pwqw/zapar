<?php

namespace App\Http\Controllers\SSO;

use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use App\Services\ConsentService;
use App\Services\SettingService;
use App\Services\UserService;
use App\Values\User\SsoUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoogleConsentController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly AuthenticationService $auth,
        private readonly SettingService $settingService,
        private readonly ConsentService $consentService,
    ) {
    }

    public function show(): View|RedirectResponse
    {
        $ssoData = session('sso_pending');

        if (!$ssoData) {
            return redirect('/');
        }

        $legalUrls = $this->settingService->getConsentLegalUrls();

        return view('sso-consent', [
            'email' => $ssoData['email'],
            'name' => $ssoData['name'],
            'terms_url' => $legalUrls['terms_url'],
            'privacy_url' => $legalUrls['privacy_url'],
        ]);
    }

    public function store(Request $request): View|RedirectResponse
    {
        $ssoData = session('sso_pending');

        if (!$ssoData) {
            return redirect('/');
        }

        $request->validate([
            'terms_accepted' => ['required', 'accepted'],
            'privacy_accepted' => ['required', 'accepted'],
            'age_verified' => ['required', 'accepted'],
        ]);

        $ssoUser = SsoUser::fromArray($ssoData);
        $user = $this->userService->createOrUpdateUserFromSso($ssoUser);

        $this->consentService->recordConsent($user, $request);

        session()->forget('sso_pending');

        return view('sso-callback')->with('token', $this->auth->logUserIn($user)->toArray());
    }
}
