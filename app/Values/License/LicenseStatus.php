<?php

namespace App\Values\License;

use App\Enums\LicenseStatus as Status;

/**
 * Plus/Koel licensing is disabled in this fork; {@see self::valid()} accepts a plain object for stubs.
 */
final class LicenseStatus
{
    private function __construct(
        public Status $status,
        public ?object $license,
    ) {}

    public function isValid(): bool
    {
        return $this->status === Status::VALID;
    }

    public function hasNoLicense(): bool
    {
        return $this->status === Status::NO_LICENSE;
    }

    public static function noLicense(): self
    {
        return new self(Status::NO_LICENSE, null);
    }

    public static function valid(object $license): self
    {
        return new self(Status::VALID, $license);
    }

    public static function invalid(object $license): self
    {
        return new self(Status::INVALID, $license);
    }

    public static function unknown(object $license): self
    {
        return new self(Status::UNKNOWN, $license);
    }
}
