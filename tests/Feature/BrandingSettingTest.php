<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\minimal_base64_encoded_image;

class BrandingSettingTest extends TestCase
{
    #[Test]
    public function adminCanUpdateBranding(): void
    {
        $this->putAs(
            'api/settings/branding',
            [
                'name' => 'Little Bird',
                'logo' => minimal_base64_encoded_image(),
                'cover' => minimal_base64_encoded_image(),
            ],
            create_admin(),
        )->assertNoContent();

        $branding = Setting::get('branding');
        self::assertSame('Little Bird', $branding['name']);
        self::assertTrue(Str::isUrl($branding['logo']));
        self::assertTrue(Str::isUrl($branding['cover']));
    }
}
