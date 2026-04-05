<?php

namespace Tests;

use App\Facades\License;
use PHPUnit\Framework\Assert;
use Tests\Fakes\FakePlusLicenseService;

/**
 * Base class for Koel Plus feature tests. When SKIP_KOEL_PLUS_TESTS=1, these tests are skipped
 * (main-branch CI alignment with upstream community). Use SKIP_KOEL_PLUS_TESTS=0 to run them locally.
 */
class PlusTestCase extends TestCase
{
    public static function isPlusSuiteSkippedByConfiguration(): bool
    {
        $value = $_ENV['SKIP_KOEL_PLUS_TESTS'] ?? getenv('SKIP_KOEL_PLUS_TESTS');

        if ($value === false || $value === null || $value === '') {
            return false;
        }

        if ($value === '0' || $value === 0) {
            return false;
        }

        return $value === '1' || $value === 1 || $value === true;
    }

    public static function enablePlusLicense(): void
    {
        self::ensurePlusTestsEnabled();

        License::swap(app(FakePlusLicenseService::class));
    }

    /**
     * Call from tests that use {@see enablePlusLicense()} but do not extend this class.
     */
    public static function ensurePlusTestsEnabled(): void
    {
        if (self::isPlusSuiteSkippedByConfiguration()) {
            Assert::markTestSkipped(
                'Koel Plus tests are disabled (SKIP_KOEL_PLUS_TESTS=1). Run with SKIP_KOEL_PLUS_TESTS=0 to include them.'
            );
        }
    }

    public function setUp(): void
    {
        self::ensurePlusTestsEnabled();

        parent::setUp();

        License::swap($this->app->make(FakePlusLicenseService::class));
    }
}
