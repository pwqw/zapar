<?php

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Models\Album;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class AlbumTest extends TestCase
{
    #[Test]
    public function updateAsOwner(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->putAs(
            "api/albums/{$album->id}",
            [
                'name' => 'Updated Album Name',
                'year' => 2023,
            ],
            $album->user
        )->assertJsonStructure(AlbumResource::JSON_STRUCTURE);

        $album->refresh();

        self::assertEquals('Updated Album Name', $album->name);
        self::assertEquals(2023, $album->year);
    }

    #[Test]
    public function adminCanUpdateIfNonOwner(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $scaryBossMan = create_admin();

        self::assertFalse($album->belongsToUser($scaryBossMan));

        // ADMIN can edit ANY album (system-wide rule)
        $this->putAs(
            "api/albums/{$album->id}",
            [
                'name' => 'Updated Album Name',
                'year' => 2023,
            ],
            $scaryBossMan
        )->assertJsonStructure(AlbumResource::JSON_STRUCTURE)
            ->assertOk();
    }

    #[Test]
    public function updateForbiddenForNonOwners(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $randomDude = create_user();

        self::assertFalse($album->belongsToUser($randomDude));

        $this->putAs(
            "api/albums/{$album->id}",
            [
                'name' => 'Updated Album Name',
                'year' => 2023,
            ],
            $randomDude
        )->assertForbidden();
    }
}
