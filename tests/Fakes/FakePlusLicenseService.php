<?php

namespace Tests\Fakes;

use App\Services\License\Contracts\LicenseServiceInterface;
use App\Values\License\LicenseStatus;

/**
 * Test double: Plus-licensed behavior for Koel Plus test suites.
 */
class FakePlusLicenseService implements LicenseServiceInterface
{
    public function getStatus(bool $checkCache = true): LicenseStatus
    {
        $license = new \stdClass();
        $license->short_key = 'test';
        $license->meta = new \stdClass();
        $license->meta->customerName = 'Test';
        $license->meta->customerEmail = 'test@example.com';

        return LicenseStatus::valid($license);
    }

    public function isPlus(): bool
    {
        return true;
    }

    public function isCommunity(): bool
    {
        return false;
    }

    public function requirePlus(): void
    {
        // no-op
    }
}
