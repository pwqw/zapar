<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Services\AuthenticationService;
use App\Services\ProxyAuthService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShareableSongController extends Controller
{
    public function __invoke(
        Request $request,
        string $id,
        ProxyAuthService $proxyAuthService,
        AuthenticationService $auth,
        SettingService $settingService,
    ): View {
        $song = Song::query()->find($id);

        if ($song === null || $song->isEpisode() || ! $song->is_public) {
            return $this->indexView($request, $proxyAuthService, $auth, $settingService);
        }

        $data = $this->indexViewData($request, $proxyAuthService, $auth, $settingService);

        $siteName = (string) koel_branding('name');
        $artistName = $song->artist?->name ?? $song->artist_name;
        $description = $this->buildSongDescription($song->title, $artistName, $siteName);

        $data['page_title'] = $song->title;
        $data['meta_description'] = Str::limit($description, 160);
        $data['og_title'] = $song->title;
        $data['og_description'] = Str::limit($description, 200);
        $data['og_image'] = $this->resolveImageUrl(
            ($song->cover ?: $song->album?->cover) ?? $song->artist?->image
        );
        $data['og_url'] = $request->url();
        $data['og_type'] = 'website';
        $data['canonical_url'] = $request->url();
        $data['shareable_redirect'] = "/#/songs/{$song->id}";

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

    private function buildSongDescription(string $title, ?string $artist, string $siteName): string
    {
        $artistPart = $artist ? " de {$artist}" : '';

        return "Escucha {$title}{$artistPart} en {$siteName}.";
    }

    private function resolveImageUrl(?string $image): ?string
    {
        if (!$image) {
            return null;
        }

        return Str::startsWith($image, ['http://', 'https://'])
            ? $image
            : image_storage_url($image);
    }
}
