<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class EpisodeTest extends TestCase
{
    #[Test]
    public function fetchEpisode(): void
    {
        $episode = Song::factory()->asEpisode()->createOne();
        $user = create_user();
        $user->podcasts()->attach($episode->podcast_id);

        $this->getAs("api/songs/{$episode->id}", $user)->assertJsonStructure(SongResource::JSON_STRUCTURE);
    }
}
