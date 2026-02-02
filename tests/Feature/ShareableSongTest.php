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
            'name' => 'Artista Uno',
            'image' => 'artist-cover.jpg',
        ]);
        $album = Album::factory()->for($artist)->create([
            'name' => 'Album Uno',
            'cover' => 'album-cover.jpg',
        ]);
        $song = Song::factory()->for($album)->public()->create([
            'title' => 'Cancion Uno',
        ]);

        $siteName = (string) koel_branding('name');
        $expectedDescription = "Escucha {$song->title} de {$artist->name} en {$siteName}.";

        $response = $this->get("/songs/{$song->id}");

        $response->assertOk();
        $response->assertSee('property="og:title" content="Cancion Uno"', false);
        $response->assertSee('property="og:description" content="' . $expectedDescription . '"', false);
        $response->assertSee('property="og:image" content="' . image_storage_url($album->cover) . '"', false);
        $response->assertSee('<title>Cancion Uno</title>', false);
        $response->assertSee('name="description" content="' . $expectedDescription . '"', false);
        $this->assertStringContainsString('\/#\/songs\/' . $song->id, $response->getContent());
    }

    #[Test]
    public function privateSongPageServesDefaultOpenGraphWithoutSongData(): void
    {
        $song = Song::factory()->private()->create([
            'title' => 'Cancion Privada',
        ]);

        $response = $this->get("/songs/{$song->id}");

        $response->assertOk();
        $response->assertDontSee('<title>Cancion Privada</title>', false);
        $response->assertDontSee('property="og:title" content="Cancion Privada"', false);
    }
}
