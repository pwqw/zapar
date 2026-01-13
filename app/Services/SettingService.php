<?php

namespace App\Services;

use App\Facades\License;
use App\Models\Setting;
use App\Values\Branding;
use Illuminate\Support\Arr;

class SettingService
{
    public function __construct(private readonly ImageStorage $imageStorage)
    {
    }

    public function getBranding(): Branding
    {
        return License::isPlus()
            ? Branding::fromArray(Arr::wrap(Setting::get('branding')))
            : Branding::make(name: config('app.name'));
    }

    public function updateMediaPath(string $path): string
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        Setting::set('media_path', $path);

        return $path;
    }

    public function updateBranding(string $name, ?string $logo, ?string $cover): void
    {
        $branding = $this->getBranding()->withName($name);

        if ($logo && $logo !== $branding->logo) {
            $branding = $branding->withLogo($this->imageStorage->storeImage($logo));
        } elseif (!$logo) {
            $branding = $branding->withoutLogo();
        }

        if ($cover && $cover !== $branding->cover) {
            $branding = $branding->withCover($this->imageStorage->storeImage($cover));
        } elseif (!$cover) {
            $branding = $branding->withoutCover();
        }

        Setting::set('branding', $branding->toArray());
    }

    /**
     * Update the welcome message and its template variables.
     *
     * @param string $message
     * @param array<int, array{name: string, url: string}> $variables
     */
    public function updateWelcomeMessage(string $message, array $variables = []): void
    {
        Setting::set('welcome_message', $message);
        Setting::set('welcome_message_variables', $variables);
    }

    /**
     * Get the configured Google Doc pages.
     *
     * @return array<int, array{title: string, slug: string, embed_url: string, default_back_url: ?string}>
     */
    public function getGoogleDocPages(): array
    {
        return Arr::wrap(Setting::get('google_doc_pages') ?? []);
    }

    /**
     * Update the Google Doc pages configuration.
     *
     * @param array<int, array{title: string, slug: string, embed_url: string, default_back_url: ?string}> $pages
     */
    public function updateGoogleDocPages(array $pages): void
    {
        Setting::set('google_doc_pages', $pages);
    }

    /**
     * Find a Google Doc page by slug.
     *
     * @param string $slug
     * @return array{title: string, slug: string, embed_url: string, default_back_url: ?string}|null
     */
    public function findGoogleDocPageBySlug(string $slug): ?array
    {
        $pages = $this->getGoogleDocPages();

        foreach ($pages as $page) {
            if ($page['slug'] === $slug) {
                return $page;
            }
        }

        return null;
    }
}
