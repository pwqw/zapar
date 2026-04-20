<?php

namespace Tests\Feature;

use App\Enums\PlayableType;
use App\Models\Organization;
use App\Models\Podcast;
use App\Models\RadioStation;
use App\Models\Song;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_moderator;

class AdminModeratorPrivateMediaBrowseTest extends TestCase
{
    #[Test]
    public function adminSeesPrivateSongsFromOtherOrganizations(): void
    {
        $otherOrg = Organization::factory()->create();
        $foreignArtist = User::factory()->artist()->create(['organization_id' => $otherOrg->id]);
        $privateSong = Song::factory()->for($foreignArtist, 'owner')->private()->create();

        $admin = create_admin();

        self::assertTrue(
            Song::query(PlayableType::SONG, $admin)->withUserContext()->whereKey($privateSong->id)->exists(),
        );
    }

    #[Test]
    public function moderatorDoesNotSeePrivateSongsFromOtherOrganizations(): void
    {
        $otherOrg = Organization::factory()->create();
        $foreignArtist = User::factory()->artist()->create(['organization_id' => $otherOrg->id]);
        $privateSong = Song::factory()->for($foreignArtist, 'owner')->private()->create();

        $moderator = create_moderator();

        self::assertFalse(
            Song::query(PlayableType::SONG, $moderator)->withUserContext()->whereKey($privateSong->id)->exists(),
        );
    }

    #[Test]
    public function moderatorSeesPrivateSongsFromTheirOrganization(): void
    {
        $org = Organization::factory()->create();
        $artist = User::factory()->artist()->create(['organization_id' => $org->id]);
        $privateSong = Song::factory()->for($artist, 'owner')->private()->create();
        $moderator = create_moderator(['organization_id' => $org->id]);

        self::assertTrue(
            Song::query(PlayableType::SONG, $moderator)->withUserContext()->whereKey($privateSong->id)->exists(),
        );
    }

    #[Test]
    public function adminSeesPrivateRadioStationsFromOtherOrganizations(): void
    {
        $otherOrg = Organization::factory()->create();
        $foreignUser = User::factory()->create(['organization_id' => $otherOrg->id]);
        $station = RadioStation::factory()->create([
            'user_id' => $foreignUser->id,
            'is_public' => false,
        ]);

        $admin = create_admin();

        self::assertTrue(
            RadioStation::query()->withUserContext(user: $admin)->whereKey($station->id)->exists(),
        );
    }

    #[Test]
    public function moderatorDoesNotSeePrivateRadioFromOtherOrganizations(): void
    {
        $otherOrg = Organization::factory()->create();
        $foreignUser = User::factory()->create(['organization_id' => $otherOrg->id]);
        $station = RadioStation::factory()->create([
            'user_id' => $foreignUser->id,
            'is_public' => false,
        ]);

        $moderator = create_moderator();

        self::assertFalse(
            RadioStation::query()->withUserContext(user: $moderator)->whereKey($station->id)->exists(),
        );
    }

    #[Test]
    public function moderatorSeesPrivateRadioInTheirOrganization(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $org->id]);
        $station = RadioStation::factory()->create([
            'user_id' => $owner->id,
            'is_public' => false,
        ]);
        $moderator = create_moderator(['organization_id' => $org->id]);

        self::assertTrue(
            RadioStation::query()->withUserContext(user: $moderator)->whereKey($station->id)->exists(),
        );
    }

    #[Test]
    public function adminSeesPrivatePodcastsFromOtherOrganizations(): void
    {
        $otherOrg = Organization::factory()->create();
        $foreignUser = User::factory()->create(['organization_id' => $otherOrg->id]);
        $podcast = Podcast::factory()->private()->create(['added_by' => $foreignUser->id]);

        $admin = create_admin();

        self::assertTrue(
            Podcast::query()->setScopedUser($admin)->accessible()->whereKey($podcast->id)->exists(),
        );
    }

    #[Test]
    public function moderatorDoesNotSeePrivatePodcastsFromOtherOrganizations(): void
    {
        $otherOrg = Organization::factory()->create();
        $foreignUser = User::factory()->create(['organization_id' => $otherOrg->id]);
        $podcast = Podcast::factory()->private()->create(['added_by' => $foreignUser->id]);

        $moderator = create_moderator();

        self::assertFalse(
            Podcast::query()->setScopedUser($moderator)->accessible()->whereKey($podcast->id)->exists(),
        );
    }

    #[Test]
    public function moderatorSeesPrivatePodcastsInTheirOrganization(): void
    {
        $org = Organization::factory()->create();
        $curator = User::factory()->create(['organization_id' => $org->id]);
        $podcast = Podcast::factory()->private()->create(['added_by' => $curator->id]);
        $moderator = create_moderator(['organization_id' => $org->id]);

        self::assertTrue(
            Podcast::query()->setScopedUser($moderator)->accessible()->whereKey($podcast->id)->exists(),
        );
    }
}
