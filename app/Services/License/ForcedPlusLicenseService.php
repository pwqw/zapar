<?php

namespace App\Services\License;

use App\Models\License;
use App\Services\LicenseService;
use App\Services\License\Contracts\LicenseServiceInterface;
use App\Values\License\LicenseStatus;

/**
 * Decorator: reports Plus/Community as if licensed, while delegating real license API to {@see LicenseService}.
 */
class ForcedPlusLicenseService implements LicenseServiceInterface
{
    public function __construct(
        private readonly LicenseService $inner,
    ) {}

    public function isPlus(): bool
    {
        return true;
    }

    public function isCommunity(): bool
    {
        return false;
    }

    public function activate(string $key): License
    {
        return $this->inner->activate($key);
    }

    public function deactivate(License $license): void
    {
        $this->inner->deactivate($license);
    }

    public function getStatus(bool $checkCache = true): LicenseStatus
    {
        return $this->inner->getStatus($checkCache);
    }
}
