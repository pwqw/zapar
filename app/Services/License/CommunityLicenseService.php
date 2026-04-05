<?php

namespace App\Services\License;

use App\Services\License\Contracts\LicenseServiceInterface;
use App\Values\License\LicenseStatus;

/**
 * Stub: no license backend; runtime behaves as Koel Plus unlocked (no I/O).
 */
class CommunityLicenseService implements LicenseServiceInterface
{
    public function getStatus(bool $checkCache = true): LicenseStatus
    {
        $license = new \stdClass();
        $license->short_key = null;
        $license->meta = new \stdClass();
        $license->meta->customerName = null;
        $license->meta->customerEmail = null;

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
        // no-op: Plus features are always available in this fork
    }
}
