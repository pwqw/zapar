<?php

namespace App\Services\License;

use App\Services\License\Contracts\LicenseServiceInterface;
use App\Values\License\LicenseStatus;

/**
 * Community edition: no license backend; bulk visibility and manager invites are restricted.
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
        return false;
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
