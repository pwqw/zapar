<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Song;
use Laravel\Scout\EngineManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class SongSearchTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config()->set('scout.driver', 'collection');
        $this->app->make(EngineManager::class)->forgetDrivers();
    }

    protected function tearDown(): void
    {
        config()->set('scout.driver', env('SCOUT_DRIVER'));
        $this->app->make(EngineManager::class)->forgetDrivers();

        parent::tearDown();
    }

    #[Test]
    public function search(): void
    {
        $user = create_user();
        Song::factory()
            ->for($user, 'owner')
            ->state(['title' => 'Foo Song'])
            ->createMany(2);

        $this->getAs('api/search/songs?q=foo', $user)->assertJsonStructure([0 => SongResource::JSON_STRUCTURE]);
    }
}
