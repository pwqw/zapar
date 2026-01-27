<?php

namespace App\Http\Controllers\SSO;

use App\Http\Controllers\Controller;
use App\Models\UserConsentLog;
use App\Services\AuthenticationService;
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

        $now = now();
        $user->update([
            'terms_accepted_at' => $now,
            'privacy_accepted_at' => $now,
            'age_verified_at' => $now,
        ]);

        $this->logConsent($user->id, $request);

        session()->forget('sso_pending');

        return view('sso-callback')->with('token', $this->auth->logUserIn($user)->toArray());
    }

    private function logConsent(int $userId, Request $request): void
    {
        $consentTypes = ['terms', 'privacy', 'age_verification'];

        foreach ($consentTypes as $type) {
            UserConsentLog::create([
                'user_id' => $userId,
                'consent_type' => $type,
                'version' => config('app.legal_version', '1.0'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'accepted' => true,
            ]);
        }
    }
}
