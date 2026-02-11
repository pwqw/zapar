<?php

namespace Tests\Feature;

use App\Models\Album;
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

        /** @var Album $album */
        $album = Album::factory()->create();

        $lastfm = $this->mock(EncyclopediaService::class);
        $lastfm->expects('getAlbumInformation')
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
                ]
            ));

        $this->getAs("api/albums/{$album->id}/information", $album->artist->user)
            ->assertJsonStructure(AlbumInformation::JSON_STRUCTURE);
    }

    #[Test]
    public function getWithoutLastfmStillReturnsValidStructure(): void
    {
        config(['koel.services.lastfm.key' => null]);
        config(['koel.services.lastfm.secret' => null]);

        /** @var Album $album */
        $album = Album::factory()->create();
        $this->getAs("api/albums/{$album->id}/information", $album->artist->user)
            ->assertJsonStructure(AlbumInformation::JSON_STRUCTURE);
    }

    #[Test]
    public function getInformationForbiddenWhenNotOwner(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $otherUser = \App\Models\User::factory()->create();

        $this->getAs("api/albums/{$album->id}/information", $otherUser)
            ->assertForbidden();
    }

    #[Test]
    public function clearInformation(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create(['cover' => 'stored-cover.jpg']);
        self::assertSame('stored-cover.jpg', $album->cover);

        $this->deleteAs("api/albums/{$album->id}/information", [], $album->artist->user)
            ->assertNoContent();

        $album->refresh();
        self::assertSame('', $album->cover);
    }

    #[Test]
    public function clearInformationForbiddenWhenNotOwner(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $otherUser = \App\Models\User::factory()->create();

        $this->deleteAs("api/albums/{$album->id}/information", [], $otherUser)
            ->assertForbidden();
    }
}
