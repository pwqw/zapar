<?php

namespace Tests\Feature;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class SongVisibilityTest extends TestCase
{
    #[Test]
    public function adminCanPrivatizeAnotherUsersSongs(): void
    {
        $admin = create_admin();
        $owner = create_user();
        $publicSongs = Song::factory(2)->for($owner, 'owner')->public()->create();

        $this->putAs('api/songs/privatize', ['songs' => $publicSongs->modelKeys()], $admin)->assertSuccessful();

        $publicSongs->each(static fn (Song $song) => self::assertFalse($song->refresh()->is_public));
    }

    #[Test]
    public function adminCanChangeVisibilityOfOwnSongs(): void
    {
        $admin = create_admin();

        $privateSongs = Song::factory()
            ->for($admin, 'owner')
            ->private()
            ->createMany(2);

        $this->putAs('api/songs/publicize', ['songs' => $privateSongs->modelKeys()], $admin)->assertNoContent();

        $privateSongs->each(static fn (Song $song) => self::assertTrue($song->refresh()->is_public));

        $publicSongs = Song::factory()
            ->for($admin, 'owner')
            ->public()
            ->createMany(2);

        $this->putAs('api/songs/privatize', ['songs' => $publicSongs->modelKeys()], $admin)->assertSuccessful();

        $publicSongs->each(static fn (Song $song) => self::assertFalse($song->refresh()->is_public));
    }
}
