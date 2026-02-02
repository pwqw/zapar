<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Services\AuthenticationService;
use App\Services\EncyclopediaService;
use App\Services\ProxyAuthService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShareableArtistController extends Controller
{
    public function __invoke(
        Request $request,
        string $id,
        ProxyAuthService $proxyAuthService,
        AuthenticationService $auth,
        SettingService $settingService,
        EncyclopediaService $encyclopediaService,
    ): View {
        $artist = Artist::query()->find($id);

        if ($artist === null || ! $this->artistHasPublicSongs($artist)) {
            return $this->indexView($request, $proxyAuthService, $auth, $settingService);
        }

        $data = $this->indexViewData($request, $proxyAuthService, $auth, $settingService);
        $tab = $request->route('tab');
        $info = $tab === 'information' ? $encyclopediaService->getArtistInformation($artist) : null;

        $siteName = (string) koel_branding('name');
        $description = $this->buildArtistDescription($artist->name, $siteName, $info?->bio['summary'] ?? null);
        $image = $this->resolveImageUrl($artist->image)
            ?? $this->resolveImageUrl($info?->image);

        $data['page_title'] = $artist->name;
        $data['meta_description'] = Str::limit($description, 160);
        $data['og_title'] = $artist->name;
        $data['og_description'] = Str::limit($description, 200);
        $data['og_image'] = $image;
        $data['og_url'] = $request->url();
        $data['og_type'] = 'website';
        $data['canonical_url'] = $request->url();
        $data['shareable_redirect'] = $this->buildShareableRedirect($artist->id, $tab);

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

    private function artistHasPublicSongs(Artist $artist): bool
    {
        return $artist->songs()->where('is_public', true)->exists();
    }

    private function buildArtistDescription(string $name, string $siteName, ?string $summary): string
    {
        $summary = $summary ? Str::of(strip_tags($summary))->squish()->toString() : null;

        if ($summary) {
            return $summary;
        }

        return "Escucha a {$name} en {$siteName}.";
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

    private function buildShareableRedirect(string $id, ?string $tab): string
    {
        if ($tab) {
            return "/#/artists/{$id}/{$tab}";
        }

        return "/#/artists/{$id}";
    }
}
