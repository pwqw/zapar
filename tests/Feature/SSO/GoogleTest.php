<?php

namespace Tests\Feature\KoelPlus\SSO;

use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GoogleTest extends TestCase
{
    #[Test]
    public function callbackWithNewUser(): void
    {
        $googleUser = Mockery::mock(GoogleUser::class, [
            'getEmail' => 'bruce@iron.com',
            'getName' => 'Bruce Dickinson',
            'getAvatar' => 'https://lh3.googleusercontent.com/a/vatar',
            'getId' => 'new-user-google-id',
        ]);

        Socialite::expects('driver->user')->andReturn($googleUser);

        $response = $this->get('auth/google/callback');
        $response->assertRedirect(route('sso.consent'));
        $response->assertSessionHas('sso_pending', [
            'provider' => 'Google',
            'id' => 'new-user-google-id',
            'email' => 'bruce@iron.com',
            'name' => 'Bruce Dickinson',
            'avatar' => 'https://lh3.googleusercontent.com/a/vatar',
        ]);
    }

    #[Test]
    public function callbackWithExistingEmail(): void
    {
        create_user(['email' => 'bruce@iron.com']);

        $googleUser = Mockery::mock(GoogleUser::class, [
            'getEmail' => 'bruce@iron.com',
            'getName' => 'Bruce Dickinson',
            'getAvatar' => 'https://lh3.googleusercontent.com/a/vatar',
            'getId' => Str::random(),
        ]);

        Socialite::expects('driver->user')->andReturn($googleUser);

        $response = $this->get('auth/google/callback');
        $response->assertOk();
        $response->assertViewIs('sso-callback');
        $response->assertViewHas('token');
    }

    #[Test]
    public function callbackWithExistingSSOUser(): void
    {
        create_user([
            'sso_provider' => 'Google',
            'sso_id' => '123',
            'email' => 'bruce@iron.com',
        ]);

        $googleUser = Mockery::mock(GoogleUser::class, [
            'getEmail' => 'bruce@iron.com',
            'getName' => 'Bruce Dickinson',
            'getAvatar' => 'https://lh3.googleusercontent.com/a/vatar',
            'getId' => '123',
        ]);

        Socialite::expects('driver->user')->andReturn($googleUser);

        $response = $this->get('auth/google/callback');
        $response->assertOk();
        $response->assertViewIs('sso-callback');
        $response->assertViewHas('token');
    }
}
