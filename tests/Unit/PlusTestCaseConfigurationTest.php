<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;
use Tests\TestCase;

class PlusTestCaseConfigurationTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('SKIP_KOEL_PLUS_TESTS');
        unset($_ENV['SKIP_KOEL_PLUS_TESTS']);

        parent::tearDown();
    }

    #[Test]
    public function plusSuiteIsNotSkippedWhenEnvIsUnset(): void
    {
        putenv('SKIP_KOEL_PLUS_TESTS');
        unset($_ENV['SKIP_KOEL_PLUS_TESTS']);

        self::assertFalse(PlusTestCase::isPlusSuiteSkippedByConfiguration());
    }

    #[Test]
    public function plusSuiteIsSkippedWhenEnvIsOne(): void
    {
        putenv('SKIP_KOEL_PLUS_TESTS=1');
        $_ENV['SKIP_KOEL_PLUS_TESTS'] = '1';

        self::assertTrue(PlusTestCase::isPlusSuiteSkippedByConfiguration());
    }
}
