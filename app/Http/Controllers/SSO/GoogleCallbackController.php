<?php

namespace App\Http\Controllers\SSO;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Services\AuthenticationService;
use App\Services\UserService;
use App\Values\User\SsoUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class GoogleCallbackController extends Controller
{
    public function __invoke(
        AuthenticationService $auth,
        UserService $userService,
        UserRepository $users,
    ): View|RedirectResponse {
        $socialiteUser = Socialite::driver('google')->user();
        $ssoUser = SsoUser::fromSocialite($socialiteUser, 'Google');

        if (!$users->findOneBySso($ssoUser)) {
            return redirect()->route('sso.consent')->with('sso_pending', $ssoUser->toArray());
        }

        $user = $userService->createOrUpdateUserFromSso($ssoUser);

        return view('sso-callback', ['token' => $auth->logUserIn($user)->toArray()]);
    }
}
