<?php

namespace Tests\Feature;

use App\Services\SettingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\minimal_base64_encoded_image;

class BrandingSettingTest extends TestCase
{
    #[Test]
    public function brandingIsAccessibleInFork(): void
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

        $branding = app(SettingService::class)->getBranding();
        self::assertSame('Little Bird', $branding->name);
        self::assertNotNull($branding->logo);
        self::assertNotNull($branding->cover);
    }
}
