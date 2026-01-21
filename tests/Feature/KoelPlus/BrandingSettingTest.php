<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Setting;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class BrandingSettingTest extends PlusTestCase
{
    #[Test]
    public function updateBrandingFromDefault(): void
    {
        $this->putAs('api/settings/branding', [
            'name' => 'Little Bird',
            'logo' => minimal_base64_encoded_image(),
            'cover' => minimal_base64_encoded_image(),
        ], create_admin())
            ->assertNoContent();

        $branding = Setting::get('branding');

        self::assertSame('Little Bird', $branding['name']);
        self::assertTrue(Str::isUrl($branding['logo']));
        self::assertTrue(Str::isUrl($branding['cover']));
    }

    #[Test]
    public function updateBrandingWithNoLogoOrCoverChanges(): void
    {
        Setting::set('branding', [
            'name' => 'Koel',
            'logo' => 'old-logo.png',
            'cover' => 'old-cover.png',
        ]);

        $this->putAs('api/settings/branding', [
            'name' => 'Little Bird',
            'logo' => image_storage_url('old-logo.png'),
            'cover' => image_storage_url('old-cover.png'),
        ], create_admin())
            ->assertNoContent();

        $branding = Setting::get('branding');

        self::assertSame('Little Bird', $branding['name']);
        self::assertSame(image_storage_url('old-logo.png'), $branding['logo']);
        self::assertSame(image_storage_url('old-cover.png'), $branding['cover']);
    }

    #[Test]
    public function updateBrandingReplacingLogoAndCover(): void
    {
        Setting::set('branding', [
            'name' => 'Koel',
            'logo' => 'old-logo.png',
            'cover' => 'old-cover.png',
        ]);

        $this->putAs('api/settings/branding', [
            'name' => 'Little Bird',
            'logo' => minimal_base64_encoded_image(),
            'cover' => minimal_base64_encoded_image(),
        ], create_admin())
            ->assertNoContent();

        $branding = Setting::get('branding');

        self::assertSame('Little Bird', $branding['name']);
        self::assertTrue(Str::isUrl($branding['logo']));
        self::assertTrue(Str::isUrl($branding['cover']));
        self::assertNotSame(image_storage_url('old-logo.png'), $branding['logo']);
        self::assertNotSame(image_storage_url('old-cover.png'), $branding['cover']);
    }

    #[Test]
    public function nonAdminCannotSetBranding(): void
    {
        $this->putAs('api/settings/branding', [
            'name' => 'Little Bird',
            'logo' => minimal_base64_encoded_image(),
            'cover' => minimal_base64_encoded_image(),
        ], create_user())
            ->assertForbidden();
    }

    #[Test]
    public function canUpdateDescriptionViaBranding(): void
    {
        $this->putAs('api/settings/branding', [
            'name' => 'Test App',
            'description' => 'Stream and organize your music collection',
        ], create_admin())
            ->assertNoContent();

        $og = Setting::get('opengraph');
        self::assertNotNull($og);
        self::assertSame('Stream and organize your music collection', $og['description']);
    }

    #[Test]
    public function canUpdateShareImageViaBranding(): void
    {
        $imageData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $this->putAs('api/settings/branding', [
            'name' => 'Test App',
            'og_image' => $imageData,
        ], create_admin())
            ->assertNoContent();

        $og = Setting::get('opengraph');
        self::assertNotNull($og);
        self::assertNotNull($og['image']);
    }

    #[Test]
    public function canUpdateShareImageUrlViaBranding(): void
    {
        $imageUrl = 'https://example.com/image.png';

        $this->putAs('api/settings/branding', [
            'name' => 'Test App',
            'og_image' => $imageUrl,
        ], create_admin())
            ->assertNoContent();

        $og = Setting::get('opengraph');
        self::assertNotNull($og);
        self::assertSame($imageUrl, $og['image']);
    }

    #[Test]
    public function descriptionCannotExceedMaxLength(): void
    {
        $this->putAs('api/settings/branding', [
            'name' => 'Test App',
            'description' => str_repeat('x', 501),
        ], create_admin())
            ->assertUnprocessable();
    }

    #[Test]
    public function canUpdatePartialOpenGraphSettingsViaBranding(): void
    {
        // First, set description
        $this->putAs('api/settings/branding', [
            'name' => 'Test App',
            'description' => 'Original Description',
        ], create_admin())
            ->assertNoContent();

        // Then update only description
        $this->putAs('api/settings/branding', [
            'name' => 'Test App',
            'description' => 'Updated Description',
        ], create_admin())
            ->assertNoContent();

        $og = Setting::get('opengraph');
        self::assertSame('Updated Description', $og['description']);
    }
}
