<?php

namespace App\Http\Controllers\SSO;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Services\AuthenticationService;
use App\Services\UserService;
use App\Values\User\SsoUser;
use Laravel\Socialite\Facades\Socialite;

class GoogleCallbackController extends Controller
{
    public function __invoke(
        AuthenticationService $auth,
        UserService $userService,
        UserRepository $userRepository
    ) {
        $googleUser = Socialite::driver('google')->user();
        $ssoUser = SsoUser::fromSocialite($googleUser, 'Google');

        $existingUser = $userRepository->findOneBySso($ssoUser);

        if ($existingUser) {
            $user = $userService->createOrUpdateUserFromSso($ssoUser);

            return view('sso-callback')->with('token', $auth->logUserIn($user)->toArray());
        }

        session()->put('sso_pending', $ssoUser->toArray());

        return redirect()->route('sso.consent');
    }
}
