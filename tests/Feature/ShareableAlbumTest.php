<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareableAlbumTest extends TestCase
{
    #[Test]
    public function publicAlbumPageServesOpenGraphMetaWithAlbumData(): void
    {
        $artist = Artist::factory()->create([
            'name' => 'Artista Album',
            'image' => 'artist-image.jpg',
        ]);
        $album = Album::factory()->for($artist)->create([
            'name' => 'Album SEO',
            'cover' => 'album-image.jpg',
        ]);
        Song::factory()->for($album)->public()->create();

        $siteName = (string) koel_branding('name');
        $expectedDescription = "Escucha el album {$album->name} de {$artist->name} en {$siteName}.";

        $response = $this->get("/albums/{$album->id}");

        $response->assertOk();
        $response->assertSee('property="og:title" content="Album SEO"', false);
        $response->assertSee('property="og:description" content="' . $expectedDescription . '"', false);
        $response->assertSee('property="og:image" content="' . image_storage_url($album->cover) . '"', false);
        $response->assertSee('<title>Album SEO</title>', false);
        $response->assertSee('name="description" content="' . $expectedDescription . '"', false);
        $this->assertStringContainsString('\/#\/albums\/' . $album->id, $response->getContent());
    }

    #[Test]
    public function albumWithoutPublicSongsServesDefaultOpenGraphWithoutAlbumData(): void
    {
        $album = Album::factory()->create([
            'name' => 'Album Privado',
        ]);

        $response = $this->get("/albums/{$album->id}");

        $response->assertOk();
        $response->assertDontSee('<title>Album Privado</title>', false);
        $response->assertDontSee('property="og:title" content="Album Privado"', false);
    }
}
