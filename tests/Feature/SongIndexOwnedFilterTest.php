<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class SongIndexOwnedFilterTest extends TestCase
{
    #[Test]
    public function ownedFilterReturnsOnlySongsWhereUserIsUploaderOrArtist(): void
    {
        $user = create_user();
        $otherUser = create_user();

        $ownSongByUpload = Song::factory()->for($user, 'owner')->create([
            'uploaded_by_id' => $user->id,
            'artist_user_id' => null,
        ]);
        $ownSongByArtist = Song::factory()->for($otherUser, 'owner')->create([
            'uploaded_by_id' => $otherUser->id,
            'artist_user_id' => $user->id,
        ]);
        $otherUserSong = Song::factory()->for($otherUser, 'owner')->create([
            'uploaded_by_id' => $otherUser->id,
            'artist_user_id' => null,
        ]);

        $response = $this->getAs("api/songs?owned=1&sort=title&order=asc&page=1", $user);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    0 => SongResource::JSON_STRUCTURE,
                ],
            ]);

        $returnedIds = collect($response->json('data'))->pluck('id')->all();
        self::assertContains($ownSongByUpload->id, $returnedIds);
        self::assertContains($ownSongByArtist->id, $returnedIds);
        self::assertNotContains($otherUserSong->id, $returnedIds);
    }

    #[Test]
    public function withoutOwnedFilterReturnsAllAccessibleSongs(): void
    {
        $user = create_user();

        Song::factory(2)->for($user, 'owner')->create(['uploaded_by_id' => $user->id]);
        Song::factory(1)->public()->create();

        $response = $this->getAs('api/songs?sort=title&order=asc&page=1', $user);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    0 => SongResource::JSON_STRUCTURE,
                ],
            ]);

        self::assertGreaterThanOrEqual(2, count($response->json('data')));
    }
}
