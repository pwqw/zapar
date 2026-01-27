<?php

namespace App\Http\Controllers;

use App\Attributes\DisabledInDemo;
use App\Services\AuthenticationService;
use App\Services\ProxyAuthService;
use App\Services\SettingService;
use Illuminate\Http\Request;

#[DisabledInDemo]
class IndexController extends Controller
{
    public function __invoke(
        Request $request,
        ProxyAuthService $proxyAuthService,
        AuthenticationService $auth,
        SettingService $settingService,
    ) {
        $data = ['token' => null];

        if (config('koel.proxy_auth.enabled')) {
            $data['token'] = optional(
                $proxyAuthService->tryGetProxyAuthenticatedUserFromRequest($request),
                static fn ($user) => $auth->logUserIn($user)->toArray()
            );
        }

        if (config('koel.misc.allow_anonymous')) {
            $data['consent_legal_urls'] = $settingService->getConsentLegalUrls();
        }

        return view('index', $data);
    }
}
