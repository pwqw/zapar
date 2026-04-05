<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareableSongTest extends TestCase
{
    #[Test]
    public function publicSongPageServesOpenGraphMetaWithSongData(): void
    {
        $artist = Artist::factory()->create([
            'name' => 'Artist One',
            'image' => 'artist-cover.jpg',
        ]);
        $album = Album::factory()->for($artist)->create([
            'name' => 'Album One',
            'cover' => 'album-cover.jpg',
        ]);
        $song = Song::factory()->for($album)->public()->create([
            'title' => 'Song One',
        ]);

        $siteName = (string) koel_branding('name');
        $expectedDescription = __('shareable.song_with_artist', [
            'title' => $song->title,
            'artist' => $artist->name,
            'site' => $siteName,
        ], 'en');

        $response = $this->get("/songs/{$song->id}");

        $response->assertOk();
        $response->assertSee('property="og:title" content="Song One"', false);
        $response->assertSee('property="og:description" content="' . $expectedDescription . '"', false);
        $response->assertSee('property="og:image" content="' . image_storage_url($album->cover) . '"', false);
        $response->assertSee('<title>Song One</title>', false);
        $response->assertSee('name="description" content="' . $expectedDescription . '"', false);
        $this->assertStringContainsString('\/#\/songs\/' . $song->id, $response->getContent());
    }

    #[Test]
    public function publicSongPageServesSpanishOpenGraphMetaWhenLocaleIsSpanish(): void
    {
        $this->app->setLocale('es');

        $artist = Artist::factory()->create([
            'name' => 'Artista Uno',
            'image' => 'artist-cover.jpg',
        ]);
        $album = Album::factory()->for($artist)->create([
            'name' => 'Álbum Uno',
            'cover' => 'album-cover.jpg',
        ]);
        $song = Song::factory()->for($album)->public()->create([
            'title' => 'Canción Uno',
        ]);

        $siteName = (string) koel_branding('name');
        $expectedDescription = __('shareable.song_with_artist', [
            'title' => $song->title,
            'artist' => $artist->name,
            'site' => $siteName,
        ], 'es');

        $response = $this->get("/songs/{$song->id}");

        $response->assertOk();
        $response->assertSee('property="og:description" content="' . $expectedDescription . '"', false);
        $response->assertSee('name="description" content="' . $expectedDescription . '"', false);
    }

    #[Test]
    public function publicSongPageUsesSongCoverWhenPresent(): void
    {
        $artist = Artist::factory()->create();
        $album = Album::factory()->for($artist)->create([
            'name' => 'Album One',
            'cover' => 'album-cover.jpg',
        ]);
        $song = Song::factory()->for($album)->public()->create([
            'title' => 'Song With Cover',
            'cover' => 'song-custom-cover.jpg',
        ]);

        $response = $this->get("/songs/{$song->id}");

        $response->assertOk();
        $response->assertSee('property="og:image" content="' . image_storage_url($song->cover) . '"', false);
    }

    #[Test]
    public function privateSongPageServesDefaultOpenGraphWithoutSongData(): void
    {
        $song = Song::factory()->private()->create([
            'title' => 'Private Song',
        ]);

        $response = $this->get("/songs/{$song->id}");

        $response->assertOk();
        $response->assertDontSee('<title>Private Song</title>', false);
        $response->assertDontSee('property="og:title" content="Private Song"', false);
    }
}
