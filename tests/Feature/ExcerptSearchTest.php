<?php

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\PodcastResource;
use App\Http\Resources\RadioStationResource;
use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Podcast;
use App\Models\RadioStation;
use App\Models\Song;
use Laravel\Scout\EngineManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ExcerptSearchTest extends TestCase
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
        Song::factory()->for($user, 'owner')->createOne(['title' => 'Foo Song']);
        Song::factory()->createOne();

        Artist::factory()->for($user)->createOne(['name' => 'Foo Fighters']);
        Artist::factory()->createOne();

        Album::factory()->for($user)->createOne(['name' => 'Foo Number Five']);
        Album::factory()->createOne();

        Podcast::factory()
            ->state(['added_by' => $user->id])
            ->hasAttached($user, relationship: 'subscribers')
            ->createOne(['title' => 'Foo Podcast']);

        RadioStation::factory()->for($user)->createOne(['name' => 'Foo Radio']);

        $this->getAs('api/search?q=foo', $user)->assertJsonStructure([
            'songs' => [0 => SongResource::JSON_STRUCTURE],
            'podcasts' => [0 => PodcastResource::JSON_STRUCTURE],
            'artists' => [0 => ArtistResource::JSON_STRUCTURE],
            'albums' => [0 => AlbumResource::JSON_STRUCTURE],
            'radio_stations' => [0 => RadioStationResource::JSON_STRUCTURE],
        ]);
    }
}
