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
        \Illuminate\Support\Facades\Cache::flush();
    }

    /**
     * @return array{terms_accepted: bool, privacy_accepted: bool, age_verified: bool, locale?: string}
     */
    private function consentPayload(array $extra = []): array
    {
        return array_merge([
            'terms_accepted' => true,
            'privacy_accepted' => true,
            'age_verified' => true,
        ], $extra);
    }

    #[Test]
    public function anonymousSessionRequiresConsent(): void
    {
        $this->postJson('api/me/anonymous', [])
            ->assertUnprocessable();

        $this->postJson('api/me/anonymous', ['terms_accepted' => true, 'privacy_accepted' => true])
            ->assertUnprocessable();
    }

    #[Test]
    public function anonymousSessionWithConsentRecordsConsent(): void
    {
        User::where('email', 'like', '%@' . User::ANONYMOUS_USER_DOMAIN)->delete();

        $this->postJson('api/me/anonymous', $this->consentPayload())
            ->assertOk();

        $anonymousUser = User::where('email', 'like', '%@' . User::ANONYMOUS_USER_DOMAIN)->first();
        self::assertNotNull($anonymousUser);
        self::assertNotNull($anonymousUser->terms_accepted_at);
        self::assertNotNull($anonymousUser->privacy_accepted_at);
        self::assertNotNull($anonymousUser->age_verified_at);

        self::assertCount(3, $anonymousUser->consentLogs);
        $types = $anonymousUser->consentLogs->pluck('consent_type')->sort()->values()->all();
        self::assertSame(['age_verification', 'privacy', 'terms'], $types);
    }

    #[Test]
    public function createAnonymousSession(): void
    {
        $response = $this->postJson('api/me/anonymous', $this->consentPayload())
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'audio-token',
            ]);

        // Verify anonymous user was created with default locale (en) name
        $anonymousUser = User::where('email', 'like', '%@' . User::ANONYMOUS_USER_DOMAIN)->first();
        self::assertNotNull($anonymousUser);
        self::assertTrue(str_ends_with($anonymousUser->email, '@' . User::ANONYMOUS_USER_DOMAIN));
        self::assertSame('strange being', $anonymousUser->name);
    }

    #[Test]
    public function createAnonymousSessionWithSpanishLocaleUsesTranslatedName(): void
    {
        User::where('email', 'like', '%@' . User::ANONYMOUS_USER_DOMAIN)->delete();

        $this->postJson('api/me/anonymous', $this->consentPayload(['locale' => 'es']))
            ->assertOk();

        $anonymousUser = User::where('email', 'like', '%@' . User::ANONYMOUS_USER_DOMAIN)->first();
        self::assertNotNull($anonymousUser);
        self::assertSame('extraño ser', $anonymousUser->name);
    }

    #[Test]
    public function reuseExistingAnonymousUserForSameIp(): void
    {
        // Clear any existing anonymous users for this test
        User::where('email', 'like', '%@' . User::ANONYMOUS_USER_DOMAIN)->delete();

        $response1 = $this->postJson('api/me/anonymous', $this->consentPayload())
            ->assertOk();

        $user1 = User::where('email', 'like', '%@' . User::ANONYMOUS_USER_DOMAIN)->first();

        $response2 = $this->postJson('api/me/anonymous', $this->consentPayload())
            ->assertOk();

        $user2 = User::where('email', 'like', '%@' . User::ANONYMOUS_USER_DOMAIN)->first();

        self::assertSame($user1->id, $user2->id);
        self::assertSame(1, User::where('email', 'like', '%@' . User::ANONYMOUS_USER_DOMAIN)->count());
    }

    #[Test]
    public function denyAnonymousSessionWhenDisabled(): void
    {
        config(['koel.misc.allow_anonymous' => false]);

        $this->postJson('api/me/anonymous', $this->consentPayload())
            ->assertForbidden();
    }

    #[Test]
    public function anonymousUserCannotDownload(): void
    {
        $this->postJson('api/me/anonymous', $this->consentPayload())
            ->assertOk();

        $anonymousUser = User::where('email', 'like', '%@' . User::ANONYMOUS_USER_DOMAIN)->first();

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
        $this->postJson('api/me/anonymous', $this->consentPayload())
            ->assertOk();

        $anonymousUser = User::where('email', 'like', '%@' . User::ANONYMOUS_USER_DOMAIN)->first();

        // Check if can create playlist
        $can = $anonymousUser->can('create', \App\Models\Playlist::class);
        self::assertFalse($can);
    }

    #[Test]
    public function anonymousUserCannotCreateInteraction(): void
    {
        $this->postJson('api/me/anonymous', $this->consentPayload())
            ->assertOk();

        $anonymousUser = User::where('email', 'like', '%@' . User::ANONYMOUS_USER_DOMAIN)->first();

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
            $response = $this->postJson('api/me/anonymous', $this->consentPayload());

            if ($i < 10) {
                $response->assertOk();
            } else {
                // 11th request should be rate limited
                $response->assertStatus(429);
            }
        }
    }
}
