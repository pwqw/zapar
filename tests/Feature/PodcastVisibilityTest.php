<?php

namespace Tests\Feature;

use App\Models\Podcast;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Models\Song as Episode;

use function Tests\create_admin;
use function Tests\create_moderator;
use function Tests\create_user;

class PodcastVisibilityTest extends TestCase
{
    #[Test]
    public function makingPodcastPublic(): void
    {
        $currentUser = create_user(['verified' => true]);
        $anotherUser = create_user();

        $externalPodcasts = Podcast::factory(2)->private()->create(['added_by' => $anotherUser->id]);

        // We can't make public podcasts that are not ours.
        $this->putAs('api/podcasts/publicize', ['podcasts' => $externalPodcasts->pluck('id')->all()], $currentUser)
            ->assertForbidden();

        // But we can our own podcasts (if verified).
        $ownPodcasts = Podcast::factory(2)->create(['added_by' => $currentUser->id]);

        $this->putAs('api/podcasts/publicize', ['podcasts' => $ownPodcasts->pluck('id')->all()], $currentUser)
            ->assertSuccessful();

        $ownPodcasts->each(static fn (Podcast $podcast) => self::assertTrue($podcast->refresh()->is_public));
    }

    #[Test]
    public function makingPodcastPrivate(): void
    {
        $currentUser = create_user(['verified' => true]);
        $anotherUser = create_user();

        $externalPodcasts = Podcast::factory(2)->public()->create(['added_by' => $anotherUser->id]);

        // We can't Mark as Private podcasts that are not ours.
        $this->putAs('api/podcasts/privatize', ['podcasts' => $externalPodcasts->pluck('id')->all()], $currentUser)
            ->assertForbidden();

        // But we can our own podcasts (if verified).
        $ownPodcasts = Podcast::factory(2)->public()->create(['added_by' => $currentUser->id]);

        $this->putAs('api/podcasts/privatize', ['podcasts' => $ownPodcasts->pluck('id')->all()], $currentUser)
            ->assertSuccessful();

        $ownPodcasts->each(static fn (Podcast $podcast) => self::assertFalse($podcast->refresh()->is_public));
    }

    #[Test]
    public function adminCanSeeAllPodcasts(): void
    {
        $admin = create_admin();
        $regularUser = create_user();

        // Create a private podcast by another user
        $privatePodcast = Podcast::factory()->private()->create(['added_by' => $regularUser->id]);

        // Create a public podcast by another user
        $publicPodcast = Podcast::factory()->public()->create(['added_by' => $regularUser->id]);

        // Admin should see all podcasts
        $response = $this->getAs('api/podcasts?favorites_only=false', $admin)
            ->assertSuccessful();

        $podcastIds = collect($response->json())->pluck('id')->all();

        self::assertContains($privatePodcast->id, $podcastIds);
        self::assertContains($publicPodcast->id, $podcastIds);
    }

    #[Test]
    public function moderatorCanSeeAllPodcasts(): void
    {
        $moderator = create_moderator();
        $regularUser = create_user();

        $privatePodcast = Podcast::factory()->private()->create(['added_by' => $regularUser->id]);
        $publicPodcast = Podcast::factory()->public()->create(['added_by' => $regularUser->id]);

        $response = $this->getAs('api/podcasts?favorites_only=false', $moderator)
            ->assertSuccessful();

        $podcastIds = collect($response->json())->pluck('id')->all();

        self::assertContains($privatePodcast->id, $podcastIds);
        self::assertContains($publicPodcast->id, $podcastIds);
    }

    #[Test]
    public function userCanSeePublicPodcastsAndOwn(): void
    {
        $user = create_user();
        $anotherUser = create_user();

        // Create a private podcast by another user (user should NOT see this)
        $otherPrivatePodcast = Podcast::factory()->private()->create(['added_by' => $anotherUser->id]);

        // Create a public podcast by another user (user should see this)
        $publicPodcast = Podcast::factory()->public()->create(['added_by' => $anotherUser->id]);

        // Create user's own private podcast (user should see this)
        $ownPrivatePodcast = Podcast::factory()->private()->create(['added_by' => $user->id]);

        $response = $this->getAs('api/podcasts?favorites_only=false', $user)
            ->assertSuccessful();

        $podcastIds = collect($response->json())->pluck('id')->all();

        self::assertNotContains($otherPrivatePodcast->id, $podcastIds);
        self::assertContains($publicPodcast->id, $podcastIds);
        self::assertContains($ownPrivatePodcast->id, $podcastIds);
    }

    #[Test]
    public function userCanAccessEpisodesOfPublicPodcastWithoutSubscription(): void
    {
        $user = create_user();
        $anotherUser = create_user();

        // Create a public podcast by another user
        $publicPodcast = Podcast::factory()->public()->create(['added_by' => $anotherUser->id]);

        // Create episodes for the podcast
        $episodes = Episode::factory(3)->asEpisode()->create(['podcast_id' => $publicPodcast->id]);

        // User is NOT subscribed to the podcast
        self::assertFalse($user->subscribedToPodcast($publicPodcast));

        // But user should be able to see the episodes
        $response = $this->getAs("api/podcasts/{$publicPodcast->id}/episodes", $user)
            ->assertSuccessful();

        $episodeIds = collect($response->json())->pluck('id')->all();

        foreach ($episodes as $episode) {
            self::assertContains($episode->id, $episodeIds);
        }
    }

    #[Test]
    public function userCannotAccessEpisodesOfPrivatePodcastFromOtherUser(): void
    {
        $user = create_user();
        $anotherUser = create_user();

        // Create a private podcast by another user
        $privatePodcast = Podcast::factory()->private()->create(['added_by' => $anotherUser->id]);

        // Create episodes for the podcast
        Episode::factory(3)->asEpisode()->create(['podcast_id' => $privatePodcast->id]);

        // User should NOT be able to see the episodes
        $this->getAs("api/podcasts/{$privatePodcast->id}/episodes", $user)
            ->assertForbidden();
    }
}
