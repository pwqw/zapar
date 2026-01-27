<?php

namespace Tests\Concerns;

use Illuminate\Contracts\Console\Kernel as Artisan;
use Illuminate\Foundation\Application;

use function Tests\test_path;

trait CreatesApplication
{
    protected string $mediaPath;
    protected string $baseUrl = 'http://localhost';

    public function createApplication(): Application
    {
        $_ENV['APP_ENV'] = 'testing';
        putenv('APP_ENV=testing');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        putenv('DB_CONNECTION=sqlite');

        /** @var Application $app */
        $app = require __DIR__ . '/../../bootstrap/app.php';

        $artisan = $app->make(Artisan::class);
        $artisan->bootstrap();

        $app->make('config')->set('database.default', 'sqlite');
        $app->make('db')->purge();

        $this->mediaPath = test_path('songs');

        return $app;
    }
}
