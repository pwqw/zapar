<?php

namespace Tests\Feature;

use App\Models\Podcast;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareablePodcastTest extends TestCase
{
    #[Test]
    public function publicPodcastPageServesOpenGraphMetaWithPodcastData(): void
    {
        $podcast = Podcast::factory()->public()->create([
            'title' => 'Mi Podcast Favorito',
            'description' => 'Descripción larga del podcast que se usa en og:description.',
            'image' => 'https://example.com/podcast-cover.png',
        ]);

        $response = $this->get("/podcasts/{$podcast->id}");

        $response->assertOk();
        $response->assertSee('property="og:title" content="Mi Podcast Favorito"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('property="og:image" content="https://example.com/podcast-cover.png"', false);
        $response->assertSee('property="og:url"', false);
        $response->assertSee("/#/podcasts/{$podcast->id}", false);
    }

    #[Test]
    public function privatePodcastPageServesDefaultOpenGraphWithoutPodcastData(): void
    {
        $podcast = Podcast::factory()->private()->create([
            'title' => 'Podcast Privado',
            'image' => 'https://example.com/private-cover.png',
        ]);

        $response = $this->get("/podcasts/{$podcast->id}");

        $response->assertOk();
        $response->assertDontSee('property="og:title" content="Podcast Privado"', false);
        $response->assertDontSee('https://example.com/private-cover.png', false);
    }

    #[Test]
    public function nonexistentPodcastPageServesIndexWithDefaultMeta(): void
    {
        $response = $this->get('/podcasts/00000000-0000-0000-0000-000000000000');

        $response->assertOk();
    }
}
