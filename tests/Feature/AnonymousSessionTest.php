<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnonymousSessionTest extends TestCase
{
    protected function disableRateLimitInTests(): bool
    {
        return false;
    }

    public function setUp(): void
    {
        parent::setUp();
        config(['koel.misc.allow_anonymous' => true]);
    }

    #[Test]
    public function createAnonymousSession(): void
    {
        $response = $this->post('api/me/anonymous')
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'audio-token',
            ]);

        // Verify anonymous user was created with default locale (en) name
        $anonymousUser = User::where('email', 'like', '%@anonymous.koel.dev')->first();
        self::assertNotNull($anonymousUser);
        self::assertTrue(str_ends_with($anonymousUser->email, '@anonymous.koel.dev'));
        self::assertSame('strange being', $anonymousUser->name);
    }

    #[Test]
    public function createAnonymousSessionWithSpanishLocaleUsesTranslatedName(): void
    {
        User::where('email', 'like', '%@anonymous.koel.dev')->delete();

        $this->postJson('api/me/anonymous', ['locale' => 'es'])
            ->assertOk();

        $anonymousUser = User::where('email', 'like', '%@anonymous.koel.dev')->first();
        self::assertNotNull($anonymousUser);
        self::assertSame('extraño ser', $anonymousUser->name);
    }

    #[Test]
    public function reuseExistingAnonymousUserForSameIp(): void
    {
        // Clear any existing anonymous users for this test
        User::where('email', 'like', '%@anonymous.koel.dev')->delete();

        $response1 = $this->post('api/me/anonymous')
            ->assertOk();

        $user1 = User::where('email', 'like', '%@anonymous.koel.dev')->first();

        $response2 = $this->post('api/me/anonymous')
            ->assertOk();

        $user2 = User::where('email', 'like', '%@anonymous.koel.dev')->first();

        self::assertSame($user1->id, $user2->id);
        self::assertSame(1, User::where('email', 'like', '%@anonymous.koel.dev')->count());
    }

    #[Test]
    public function denyAnonymousSessionWhenDisabled(): void
    {
        config(['koel.misc.allow_anonymous' => false]);

        $this->post('api/me/anonymous')
            ->assertForbidden();
    }

    #[Test]
    public function anonymousUserCannotDownload(): void
    {
        $response = $this->post('api/me/anonymous')
            ->assertOk();

        $token = $response->json('token');
        $anonymousUser = User::where('email', 'like', '%@anonymous.koel.dev')->first();

        // Create a test song owned by another user
        $owner = User::factory()->create();
        $song = \App\Models\Song::factory()->create(['owner_id' => $owner->id]);

        // Try to check download policy
        $can = $anonymousUser->can('download', $song);
        self::assertFalse($can);
    }

    #[Test]
    public function anonymousUserCannotCreatePlaylist(): void
    {
        $this->post('api/me/anonymous')
            ->assertOk();

        $anonymousUser = User::where('email', 'like', '%@anonymous.koel.dev')->first();

        // Check if can create playlist
        $can = $anonymousUser->can('create', \App\Models\Playlist::class);
        self::assertFalse($can);
    }

    #[Test]
    public function anonymousUserCannotCreateInteraction(): void
    {
        $this->post('api/me/anonymous')
            ->assertOk();

        $anonymousUser = User::where('email', 'like', '%@anonymous.koel.dev')->first();

        // Check if can create interaction (favorite)
        $can = $anonymousUser->can('create', \App\Models\Interaction::class);
        self::assertFalse($can);
    }

    #[Test]
    public function rateLimitingApplies(): void
    {
        \Illuminate\Support\Facades\Cache::flush();

        // Make 11 requests quickly (limit is 10 per minute)
        for ($i = 0; $i < 11; $i++) {
            $response = $this->post('api/me/anonymous');

            if ($i < 10) {
                $response->assertOk();
            } else {
                // 11th request should be rate limited
                $response->assertStatus(429);
            }
        }
    }
}
