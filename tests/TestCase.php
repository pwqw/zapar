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
        // Forzar SQLite en memoria antes de boot para que LazilyRefreshDatabase migre sobre esta conexión
        putenv('DB_CONNECTION=sqlite');
        $_ENV['DB_CONNECTION'] = 'sqlite';

        parent::setUp();

        $driver = config('database.connections.' . config('database.default') . '.driver');
        if ($driver !== 'sqlite') {
            throw new \RuntimeException(
                'Los tests deben usar SQLite driver, pero se detectó: ' . $driver . '.'
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
