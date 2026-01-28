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
        $response->assertSee('name="twitter:card" content="summary_large_image"', false);
        $response->assertSee('name="twitter:title" content="Mi Podcast Favorito"', false);
        $response->assertSee('name="twitter:image" content="https://example.com/podcast-cover.png"', false);
        $response->assertSee('rel="canonical"', false);
        $this->assertStringContainsString("podcasts/{$podcast->id}", $response->getContent());
    }

    #[Test]
    public function publicPodcastPageServesSeoTitleAndMetaDescription(): void
    {
        $podcast = Podcast::factory()->public()->create([
            'title' => 'Podcast con SEO',
            'description' => 'Resumen breve para buscadores y tarjetas sociales.',
            'image' => 'https://example.com/cover.png',
        ]);

        $response = $this->get("/podcasts/{$podcast->id}");

        $response->assertOk();
        $response->assertSee('<title>Podcast con SEO</title>', false);
        $response->assertSee('name="description" content="Resumen breve para buscadores y tarjetas sociales."', false);
    }

    #[Test]
    public function privatePodcastPageServesDefaultOpenGraphWithoutPodcastData(): void
    {
        $podcast = Podcast::factory()->private()->create([
            'title' => 'Podcast Privado',
            'description' => 'No debe mostrarse en meta.',
            'image' => 'https://example.com/private-cover.png',
        ]);

        $response = $this->get("/podcasts/{$podcast->id}");

        $response->assertOk();
        $response->assertDontSee('<title>Podcast Privado</title>', false);
        $response->assertDontSee('property="og:title" content="Podcast Privado"', false);
        $response->assertDontSee('name="description" content="No debe mostrarse en meta."', false);
        $response->assertDontSee('https://example.com/private-cover.png', false);
    }

    #[Test]
    public function nonexistentPodcastPageServesIndexWithDefaultMeta(): void
    {
        $response = $this->get('/podcasts/00000000-0000-0000-0000-000000000000');

        $response->assertOk();
    }
}
