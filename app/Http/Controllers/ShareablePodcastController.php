<?php

namespace App\Http\Controllers;

use App\Models\Podcast;
use App\Services\AuthenticationService;
use App\Services\ProxyAuthService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShareablePodcastController extends Controller
{
    public function __invoke(
        Request $request,
        string $id,
        ProxyAuthService $proxyAuthService,
        AuthenticationService $auth,
        SettingService $settingService,
    ): View {
        $podcast = Podcast::query()->find($id);

        if ($podcast === null || ! $podcast->is_public) {
            return $this->indexView($request, $proxyAuthService, $auth, $settingService);
        }

        $data = $this->indexViewData($request, $proxyAuthService, $auth, $settingService);

        $ogImage = Str::startsWith($podcast->image, ['http://', 'https://'])
            ? $podcast->image
            : image_storage_url($podcast->image);

        $data['og_title'] = $podcast->title;
        $data['og_description'] = Str::limit(strip_tags($podcast->description), 200);
        $data['og_image'] = $ogImage;
        $data['og_url'] = $request->url();
        $data['og_type'] = 'website';
        $data['shareable_redirect'] = "/#/podcasts/{$podcast->id}";

        return view('index', $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function indexViewData(
        Request $request,
        ProxyAuthService $proxyAuthService,
        AuthenticationService $auth,
        SettingService $settingService,
    ): array {
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

        return $data;
    }

    private function indexView(
        Request $request,
        ProxyAuthService $proxyAuthService,
        AuthenticationService $auth,
        SettingService $settingService,
    ): View {
        return view('index', $this->indexViewData($request, $proxyAuthService, $auth, $settingService));
    }
}
