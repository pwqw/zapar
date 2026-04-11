<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\User;
use App\Services\EncyclopediaService;
use App\Values\Album\AlbumInformation;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumInformationTest extends TestCase
{
    #[Test]
    public function getInformation(): void
    {
        config(['koel.services.lastfm.key' => 'foo']);
        config(['koel.services.lastfm.secret' => 'geheim']);
        $album = Album::factory()->createOne();

        $lastfm = $this->mock(EncyclopediaService::class);
        $lastfm
            ->expects('getAlbumInformation')
            ->with(Mockery::on(static fn (Album $a) => $a->is($album)))
            ->andReturn(AlbumInformation::make(
                url: 'https://lastfm.com/album/foo',
                cover: 'https://lastfm.com/cover/foo',
                wiki: [
                    'summary' => 'foo',
                    'full' => 'bar',
                ],
                tracks: [
                    [
                        'title' => 'foo',
                        'length' => 123,
                        'url' => 'https://lastfm.com/track/foo',
                    ],
                    [
                        'title' => 'bar',
                        'length' => 456,
                        'url' => 'https://lastfm.com/track/bar',
                    ],
                ],
            ));

        $owner = User::query()->findOrFail($album->artist->user_id);

        $this->getAs("api/albums/{$album->id}/information", $owner)->assertJsonStructure(AlbumInformation::JSON_STRUCTURE);
    }

    #[Test]
    public function getWithoutLastfmStillReturnsValidStructure(): void
    {
        config(['koel.services.lastfm.key' => null]);
        config(['koel.services.lastfm.secret' => null]);

        $album = Album::factory()->createOne();
        $owner = User::query()->findOrFail($album->artist->user_id);

        $this->getAs(
            "api/albums/{$album->id}/information",
            $owner,
        )->assertJsonStructure(AlbumInformation::JSON_STRUCTURE);
    }

    #[Test]
    public function clearInformation(): void
    {
        $album = Album::factory()->createOne();
        $owner = User::query()->findOrFail($album->artist->user_id);

        $this->deleteAs("api/albums/{$album->id}/information", [], $owner)->assertNoContent();
    }
}
