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
            'title' => 'My Favorite Podcast',
            'description' => 'Long podcast description used for og:description.',
            'image' => 'https://example.com/podcast-cover.png',
        ]);

        $response = $this
            ->withoutVite()
            ->get("/podcasts/{$podcast->id}");

        $response->assertOk();
        $response->assertSee('property="og:title" content="My Favorite Podcast"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('property="og:image" content="https://example.com/podcast-cover.png"', false);
        $response->assertSee('property="og:url"', false);
        $response->assertSee('name="twitter:card" content="summary_large_image"', false);
        $response->assertSee('name="twitter:title" content="My Favorite Podcast"', false);
        $response->assertSee('name="twitter:image" content="https://example.com/podcast-cover.png"', false);
        $response->assertSee('rel="canonical"', false);
        $this->assertStringContainsString("podcasts/{$podcast->id}", $response->getContent());
    }

    #[Test]
    public function publicPodcastPageServesSeoTitleAndMetaDescription(): void
    {
        $podcast = Podcast::factory()->public()->create([
            'title' => 'SEO Podcast',
            'description' => 'Short summary for search engines and social cards.',
            'image' => 'https://example.com/cover.png',
        ]);

        $response = $this
            ->withoutVite()
            ->get("/podcasts/{$podcast->id}");

        $response->assertOk();
        $response->assertSee('<title>SEO Podcast</title>', false);
        $response->assertSee('name="description" content="Short summary for search engines and social cards."', false);
    }

    #[Test]
    public function privatePodcastPageServesDefaultOpenGraphWithoutPodcastData(): void
    {
        $podcast = Podcast::factory()->private()->create([
            'title' => 'Private Podcast',
            'description' => 'Must not appear in meta.',
            'image' => 'https://example.com/private-cover.png',
        ]);

        $response = $this
            ->withoutVite()
            ->get("/podcasts/{$podcast->id}");

        $response->assertOk();
        $response->assertDontSee('<title>Private Podcast</title>', false);
        $response->assertDontSee('property="og:title" content="Private Podcast"', false);
        $response->assertDontSee('name="description" content="Must not appear in meta."', false);
        $response->assertDontSee('https://example.com/private-cover.png', false);
    }

    #[Test]
    public function nonexistentPodcastPageServesIndexWithDefaultMeta(): void
    {
        $response = $this
            ->withoutVite()
            ->get('/podcasts/00000000-0000-0000-0000-000000000000');

        $response->assertOk();
    }
}
