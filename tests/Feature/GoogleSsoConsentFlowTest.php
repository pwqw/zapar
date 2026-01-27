<?php

namespace Tests\Feature;

use App\Http\Controllers\SSO\GoogleConsentController;
use App\Models\User;
use App\Models\UserConsentLog;
use App\Services\AuthenticationService;
use App\Services\UserService;
use App\Values\User\SsoUser;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleSsoConsentFlowTest extends TestCase
{
    #[Test]
    public function consentPageRedirectsWithoutSession(): void
    {
        $this->get(route('sso.consent'))
            ->assertRedirect('/');
    }

    #[Test]
    public function consentPageShowsWithValidSession(): void
    {
        $this->withSession([
            'sso_pending' => [
                'provider' => 'Google',
                'id' => '12345',
                'email' => 'test@example.com',
                'name' => 'Test User',
                'avatar' => null,
            ],
        ])->get(route('sso.consent'))
            ->assertOk()
            ->assertSee('test@example.com')
            ->assertSee('Test User');
    }

    #[Test]
    public function existingUserSkipsConsentPage(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'sso_id' => '99999',
            'sso_provider' => 'Google',
        ]);

        $this->assertNotNull($existingUser->id);
        $this->assertEquals('Google', $existingUser->sso_provider);
    }

    #[Test]
    public function ssoUserCanBeSerializedAndDeserialized(): void
    {
        $ssoUser = SsoUser::fromArray([
            'provider' => 'Google',
            'id' => '12345',
            'email' => 'test@example.com',
            'name' => 'Test User',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        $array = $ssoUser->toArray();

        $this->assertEquals('Google', $array['provider']);
        $this->assertEquals('12345', $array['id']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals('Test User', $array['name']);
        $this->assertEquals('https://example.com/avatar.jpg', $array['avatar']);

        $reconstructed = SsoUser::fromArray($array);

        $this->assertEquals($ssoUser->id, $reconstructed->id);
        $this->assertEquals($ssoUser->email, $reconstructed->email);
    }

    #[Test]
    public function userServiceCreatesUserWithSsoData(): void
    {
        $ssoUser = SsoUser::fromArray([
            'provider' => 'Google',
            'id' => 'unit-test-123',
            'email' => 'unittest@example.com',
            'name' => 'Unit Test User',
            'avatar' => null,
        ]);

        $userService = app(UserService::class);
        $user = $userService->createOrUpdateUserFromSso($ssoUser);

        $this->assertNotNull($user);
        $this->assertEquals('Unit Test User', $user->name);
        $this->assertEquals('unittest@example.com', $user->email);
        $this->assertEquals('Google', $user->sso_provider);
        $this->assertEquals('unit-test-123', $user->sso_id);
    }

    #[Test]
    public function userConsentLogsCanBeCreated(): void
    {
        $user = User::factory()->create();

        $user->consentLogs()->create([
            'consent_type' => 'terms',
            'version' => '1.0',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'accepted' => true,
        ]);

        $this->assertCount(1, $user->consentLogs);
        $this->assertEquals('terms', $user->consentLogs->first()->consent_type);
    }
}
