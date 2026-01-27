<?php

namespace Tests;

use App\Helpers\Ulid;
use App\Helpers\Uuid;
use App\Models\Album;
use App\Services\MediaBrowser;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\AssertsArraySubset;
use Tests\Concerns\CreatesApplication;
use Tests\Concerns\MakesHttpRequests;

abstract class TestCase extends BaseTestCase
{
    use AssertsArraySubset;
    use CreatesApplication;
    use LazilyRefreshDatabase;
    use MakesHttpRequests;

    /**
     * @var Filesystem The backup of the real filesystem instance, to restore after tests.
     * This is necessary because we might be mocking the File facade in tests, and at the same time
     * we delete test resources during suite's teardown.
     */
    private Filesystem $fileSystem;

    protected function disableRateLimitInTests(): bool
    {
        return true;
    }

    public function setUp(): void
    {
        parent::setUp();

        // ⚠️ PROTECTION: Ensure tests use SQLite (in-memory or persistent testing)
        $dbConnection = config('database.default');

        // Allow both 'sqlite' (in-memory) and 'sqlite-persistent' (file-based SQLite for dev)
        // The important thing is we're NOT using MySQL/PostgreSQL with real data
        $allowedConnections = ['sqlite', 'sqlite-persistent'];
        if (!in_array($dbConnection, $allowedConnections, true)) {
            throw new \RuntimeException(
                "Los tests deben usar SQLite, pero se detectó: {$dbConnection}. " .
                "Esto podría borrar la base de datos de desarrollo. " .
                "Conexiones permitidas: " . implode(', ', $allowedConnections)
            );
        }

        // For sqlite-persistent, verify the driver is SQLite (not MySQL/PostgreSQL)
        $driver = config("database.connections.{$dbConnection}.driver");
        if ($driver !== 'sqlite') {
            throw new \RuntimeException(
                "Los tests deben usar SQLite driver, pero se detectó: {$driver}."
            );
        }

        // Disable rate limiting in tests (override disableRateLimitInTests() to keep it in specific tests)
        if ($this->disableRateLimitInTests()) {
            $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        }

        $this->fileSystem = File::getFacadeRoot();

        // During the Album's `saved` event, we attempt to generate a thumbnail by dispatching a job.
        // Disable this to avoid noise and side effects.
        Album::getEventDispatcher()?->forget('eloquent.saved: ' . Album::class);

        self::createSandbox();
    }

    protected function tearDown(): void
    {
        File::swap($this->fileSystem);
        self::destroySandbox();
        MediaBrowser::clearCache();

        Ulid::unfreeze();
        Uuid::unfreeze();

        parent::tearDown();
    }

    private static function createSandbox(): void
    {
        config([
            'koel.image_storage_dir' => 'sandbox/img/storage/',
            'koel.artifacts_path' => public_path('sandbox/artifacts/'),
        ]);

        File::ensureDirectoryExists(public_path(config('koel.image_storage_dir')));
        File::ensureDirectoryExists(public_path('sandbox/media/'));
    }

    private static function destroySandbox(): void
    {
        File::deleteDirectory(public_path('sandbox'));
    }
}
