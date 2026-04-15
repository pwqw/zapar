<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class SongTest extends TestCase
{
    #[Test]
    public function indexReturnsPaginatedSongs(): void
    {
        Song::factory()->count(3)->create();

        $this->getAs('api/songs')->assertJsonStructure(SongResource::PAGINATION_JSON_STRUCTURE);
        $this->getAs('api/songs?sort=title&order=desc')->assertJsonStructure(SongResource::PAGINATION_JSON_STRUCTURE);
    }

    #[Test]
    public function showReturnsExpectedSongStructure(): void
    {
        /** @var Song $song */
        $song = Song::factory()->public()->create();

        $this->getAs("api/songs/{$song->id}")
            ->assertSuccessful()
            ->assertJsonStructure(SongResource::JSON_STRUCTURE);
    }

    #[Test]
    public function showSongPolicy(): void
    {
        $user = create_user();

        /** @var Song $publicSong */
        $publicSong = Song::factory()->public()->create();

        // We can access public songs.
        $this->getAs("api/songs/{$publicSong->id}", $user)->assertSuccessful();

        /** @var Song $ownPrivateSong */
        $ownPrivateSong = Song::factory()->for($user, 'owner')->private()->create();

        // We can access our own private songs.
        $this->getAs("api/songs/{$ownPrivateSong->id}", $user)->assertSuccessful();

        /** @var Song $externalUnownedSong */
        $externalUnownedSong = Song::factory()->private()->create();

        // But we can't access private songs that are not ours.
        $this->getAs("api/songs/{$externalUnownedSong->id}", $user)->assertForbidden();
    }

    #[Test]
    public function editSongsPolicy(): void
    {
        $currentUser = create_user();
        $anotherUser = create_user();

        $externalUnownedSongs = Song::factory(2)->for($anotherUser, 'owner')->private()->create();

        // We can't edit songs that are not ours.
        $this->putAs('api/songs', [
            'songs' => $externalUnownedSongs->modelKeys(),
            'data' => [
                'title' => 'New Title',
            ],
        ], $currentUser)->assertForbidden();

        // Even if some of the songs are owned by us, we still can't edit them.
        $mixedSongs = $externalUnownedSongs->merge(Song::factory(2)->for($currentUser, 'owner')->create());

        $this->putAs('api/songs', [
            'songs' => $mixedSongs->modelKeys(),
            'data' => [
                'title' => 'New Title',
            ],
        ], $currentUser)->assertForbidden();

        // But we can edit our own songs.
        $ownSongs = Song::factory(2)->for($currentUser, 'owner')->create();

        $this->putAs('api/songs', [
            'songs' => $ownSongs->modelKeys(),
            'data' => [
                'title' => 'New Title',
            ],
        ], $currentUser)->assertSuccessful();
    }

    #[Test]
    public function updateSongCanClearAlbumName(): void
    {
        $user = create_user();

        /** @var Song $song */
        $song = Song::factory()->for($user, 'owner')->create();

        $this->putAs('api/songs', [
            'songs' => [$song->id],
            'data' => [
                'album_name' => '',
            ],
        ], $user)->assertSuccessful();

        $song->refresh();

        self::assertSame('', $song->album_name);
        self::assertSame('', $song->album->name);
    }

    #[Test]
    public function deleteSongsPolicy(): void
    {
        $currentUser = create_user();
        $anotherUser = create_user();

        $externalUnownedSongs = Song::factory(2)->for($anotherUser, 'owner')->private()->create();

        // We can't delete songs that are not ours.
        $this->deleteAs('api/songs', ['songs' => $externalUnownedSongs->modelKeys()], $currentUser)
            ->assertForbidden();

        // Even if some of the songs are owned by us, we still can't delete them.
        $mixedSongs = $externalUnownedSongs->merge(Song::factory(2)->for($currentUser, 'owner')->create());

        $this->deleteAs('api/songs', ['songs' => $mixedSongs->modelKeys()], $currentUser)
            ->assertForbidden();

        // But we can delete our own songs.
        $ownSongs = Song::factory(2)->for($currentUser, 'owner')->create();
        $ownSongIds = $ownSongs->modelKeys();

        $this->deleteAs('api/songs', ['songs' => $ownSongs->modelKeys()], $currentUser)
            ->assertSuccessful();

        Song::query()->whereIn('id', $ownSongIds)->get()->each($this->assertModelMissing(...));
        $externalUnownedSongs->each($this->assertModelExists(...));
    }

    #[Test]
    public function updateSongsMetadata(): void
    {
        $song = Song::factory()->for(create_admin(), 'owner')->create([
            'title' => 'Old Title',
            'track' => 3,
            'disc' => 2,
            'lyrics' => 'Old lyrics',
        ]);

        $this->putAs('api/songs', [
            'songs' => [$song->id],
            'data' => [
                'title' => 'New Title',
                'lyrics' => 'New lyrics',
                'track' => 7,
                'disc' => 1,
            ],
        ], $song->owner)->assertSuccessful();

        $song->refresh();

        self::assertSame('New Title', (string) $song->title);
        self::assertSame('New lyrics', (string) $song->lyrics);
        self::assertSame(7, $song->track);
        self::assertSame(1, $song->disc);
    }

    #[Test]
    public function markSongsAsPublic(): void
    {
        $user = create_user(['verified' => true]);

        $songs = Song::factory(2)->for($user, 'owner')->private()->create();

        $this->putAs('api/songs/publicize', ['songs' => $songs->modelKeys()], $user)
            ->assertSuccessful();

        $songs->each(static function (Song $song): void {
            $song->refresh();
            self::assertTrue($song->is_public);
        });
    }

    #[Test]
    public function markSongsAsPrivate(): void
    {
        $user = create_user();

        $songs = Song::factory(2)->for($user, 'owner')->public()->create();

        $this->putAs('api/songs/privatize', ['songs' => $songs->modelKeys()], $user)
            ->assertSuccessful();

        $songs->each(static function (Song $song): void {
            $song->refresh();
            self::assertFalse($song->is_public);
        });
    }

    #[Test]
    public function publicizingOrPrivatizingSongsRequiresOwnership(): void
    {
        $songs = Song::factory(2)->public()->create();

        $this->putAs('api/songs/privatize', ['songs' => $songs->modelKeys()])
            ->assertForbidden();

        $otherSongs = Song::factory(2)->private()->create();

        $this->putAs('api/songs/publicize', ['songs' => $otherSongs->modelKeys()])
            ->assertForbidden();
    }
}
