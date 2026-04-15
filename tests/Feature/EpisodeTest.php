<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class EpisodeTest extends TestCase
{
    #[Test]
    public function fetchEpisode(): void
    {
        /** @var Song $episode */
        $episode = Song::factory()->asEpisode()->create();

        // Use admin to access any episode (ADMIN can access any podcast)
        $this->getAs("api/songs/{$episode->id}", create_admin())
            ->assertJsonStructure(SongResource::JSON_STRUCTURE);
    }
}
