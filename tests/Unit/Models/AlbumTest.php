<?php

namespace Tests\Unit\Models;

use App\Models\Album;
use App\Models\Artist;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    #[Test]
    public function existingAlbumCanBeRetrievedUsingArtistAndName(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        /** @var Album $album */
        $album = Album::factory()->for($artist)->for($artist->user)->create();

        self::assertTrue(Album::getOrCreate($artist, $album->name)->is($album));
    }

    #[Test]
    public function newAlbumIsAutomaticallyCreatedWithUserAndArtistAndName(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $name = 'Foo';

        self::assertNull(Album::query()->whereBelongsTo($artist)->where('name', $name)->first());

        $album = Album::getOrCreate($artist, $name);
        self::assertSame('Foo', $album->name);
        self::assertTrue($album->artist->is($artist));
    }

    #[Test]
    public function newAlbumWithNullNameIsCreatedAsUnknownAlbum(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $album = Album::getOrCreate($artist, null);

        self::assertSame('Unknown Album', $album->name);
    }

    /** @return array<mixed> */
    public static function provideEmptyOrWhitespaceAlbumNames(): array
    {
        return [
            [''],
            ['  '],
        ];
    }

    #[DataProvider('provideEmptyOrWhitespaceAlbumNames')]
    #[Test]
    public function newAlbumWithEmptyOrWhitespaceNameKeepsEmptyName(string $name): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $album = Album::getOrCreate($artist, $name);

        self::assertSame('', $album->name);
    }
}
