<?php

namespace Tests\Unit\Services\License;

use App\Enums\LicenseStatus as LicenseStatusEnum;
use App\Services\License\CommunityLicenseService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommunityLicenseServiceTest extends TestCase
{
    #[Test]
    public function reportsPlusUnlockedAndValidStatusStub(): void
    {
        $service = new CommunityLicenseService();

        $this->assertFalse($service->isCommunity());
        $this->assertFalse($service->isPlus());
        $this->assertTrue($service->getStatus()->isValid());
        $this->assertSame(LicenseStatusEnum::VALID, $service->getStatus()->status);
    }

    #[Test]
    public function requirePlusIsNoOp(): void
    {
        $this->expectNotToPerformAssertions();

        (new CommunityLicenseService())->requirePlus();
    }
}
