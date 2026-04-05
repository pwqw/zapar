<?php

namespace App\Services\License\Contracts;

use App\Values\License\LicenseStatus;

/**
 * Merge-compatibility contract for upstream; this fork binds {@see \App\Services\License\CommunityLicenseService}
 * (no payment API or persisted licenses).
 */
interface LicenseServiceInterface
{
    public function getStatus(bool $checkCache = true): LicenseStatus;

    public function isPlus(): bool;

    public function isCommunity(): bool;

    public function requirePlus(): void;
}
