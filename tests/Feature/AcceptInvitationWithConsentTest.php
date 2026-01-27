<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserConsentLog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user_prospect;

class AcceptInvitationWithConsentTest extends TestCase
{
    #[Test]
    public function acceptInvitationRequiresConsent(): void
    {
        $prospect = create_user_prospect();

        $this->postJson('api/invitations/accept', [
            'token' => $prospect->invitation_token,
            'name' => 'Test User',
            'password' => 'SecureP@ssw0rd!',
        ])->assertUnprocessable();
    }

    #[Test]
    public function acceptInvitationFailsWithPartialConsent(): void
    {
        $prospect = create_user_prospect();

        $this->postJson('api/invitations/accept', [
            'token' => $prospect->invitation_token,
            'name' => 'Test User',
            'password' => 'SecureP@ssw0rd!',
            'terms_accepted' => true,
            'privacy_accepted' => true,
            // missing age_verified
        ])->assertUnprocessable();
    }

    #[Test]
    public function acceptInvitationSucceedsWithFullConsent(): void
    {
        $prospect = create_user_prospect();

        $this->post('api/invitations/accept', [
            'token' => $prospect->invitation_token,
            'name' => 'Test User',
            'password' => 'SecureP@ssw0rd!',
            'terms_accepted' => true,
            'privacy_accepted' => true,
            'age_verified' => true,
        ])->assertOk();

        $user = User::find($prospect->id);

        $this->assertNotNull($user->terms_accepted_at);
        $this->assertNotNull($user->privacy_accepted_at);
        $this->assertNotNull($user->age_verified_at);
        $this->assertNull($user->invitation_token);
    }

    #[Test]
    public function acceptInvitationCreatesConsentLogs(): void
    {
        $prospect = create_user_prospect();

        $this->post('api/invitations/accept', [
            'token' => $prospect->invitation_token,
            'name' => 'Test User',
            'password' => 'SecureP@ssw0rd!',
            'terms_accepted' => true,
            'privacy_accepted' => true,
            'age_verified' => true,
        ])->assertOk();

        $this->assertDatabaseHas('user_consent_logs', [
            'user_id' => $prospect->id,
            'consent_type' => 'terms',
            'accepted' => true,
        ]);

        $this->assertDatabaseHas('user_consent_logs', [
            'user_id' => $prospect->id,
            'consent_type' => 'privacy',
            'accepted' => true,
        ]);

        $this->assertDatabaseHas('user_consent_logs', [
            'user_id' => $prospect->id,
            'consent_type' => 'age_verification',
            'accepted' => true,
        ]);

        $this->assertCount(3, UserConsentLog::where('user_id', $prospect->id)->get());
    }
}
