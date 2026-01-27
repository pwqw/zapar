<?php

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Models\Artist;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ArtistAlbumTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        $user = create_user();
        /** @var Artist $artist */
        $artist = Artist::factory()->for($user)->create();
        Album::factory(5)->for($artist)->create(['user_id' => $user->id]);

        $this->getAs("api/artists/{$artist->id}/albums", $user)
            ->assertJsonStructure([0 => AlbumResource::JSON_STRUCTURE]);
    }
}
