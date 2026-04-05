<?php

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtistAlbumTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        $artist = Artist::factory()->createOne();
        /** @var User $owner */
        $owner = $artist->user;
        Album::factory()->for($artist)->for($owner)->createMany(5);

        $this->getAs("api/artists/{$artist->id}/albums", $owner)->assertJsonStructure([0 => AlbumResource::JSON_STRUCTURE]);
    }
}
