<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\EncyclopediaService;
use App\Values\Artist\ArtistInformation;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareableArtistTest extends TestCase
{
    #[Test]
    public function artistInformationPageFallsBackToEncouragingDescription(): void
    {
        $artist = Artist::factory()->create([
            'name' => 'Artista SEO',
            'image' => 'artist-seo.jpg',
        ]);
        $album = Album::factory()->for($artist)->create();
        Song::factory()->for($album)->public()->create();

        $encyclopedia = $this->mock(EncyclopediaService::class);
        $encyclopedia->expects('getArtistInformation')
            ->with(Mockery::on(static fn (Artist $a) => $a->is($artist)))
            ->andReturn(ArtistInformation::make());

        $siteName = (string) koel_branding('name');
        $expectedDescription = "Escucha a {$artist->name} en {$siteName}.";

        $response = $this->get("/artists/{$artist->id}/information");

        $response->assertOk();
        $response->assertSee('property="og:title" content="Artista SEO"', false);
        $response->assertSee('property="og:description" content="' . $expectedDescription . '"', false);
        $response->assertSee('property="og:image" content="' . image_storage_url($artist->image) . '"', false);
        $response->assertSee('<title>Artista SEO</title>', false);
        $response->assertSee('name="description" content="' . $expectedDescription . '"', false);
        $this->assertStringContainsString('\/#\/artists\/' . $artist->id . '\/information', $response->getContent());
    }

    #[Test]
    public function artistWithoutPublicSongsServesDefaultOpenGraphWithoutArtistData(): void
    {
        $artist = Artist::factory()->create([
            'name' => 'Artista Privado',
        ]);

        $response = $this->get("/artists/{$artist->id}");

        $response->assertOk();
        $response->assertDontSee('<title>Artista Privado</title>', false);
        $response->assertDontSee('property="og:title" content="Artista Privado"', false);
    }
}
