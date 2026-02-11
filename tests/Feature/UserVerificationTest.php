<?php

namespace Tests\Feature;

use App\Enums\Acl\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_artist;
use function Tests\create_manager;
use function Tests\create_moderator;
use function Tests\create_user;

class UserVerificationTest extends TestCase
{
    #[Test]
    public function adminCanVerifyAnyUser(): void
    {
        $admin = create_admin();
        $user = create_user();

        self::assertFalse($user->verified);
        self::assertTrue($admin->canVerify($user));

        $this->patchAs("api/users/{$user->public_id}", [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'verified' => true,
        ], $admin)
            ->assertSuccessful();

        $user->refresh();
        self::assertTrue($user->verified);
    }

    #[Test]
    public function moderatorCanVerifyUsersInTheirOrganization(): void
    {
        $moderator = create_moderator();
        $artist = create_artist(['organization_id' => $moderator->organization_id]);

        self::assertFalse($artist->verified);
        self::assertTrue($moderator->canVerify($artist));

        $this->patchAs("api/users/{$artist->public_id}", [
            'name' => $artist->name,
            'email' => $artist->email,
            'role' => $artist->role->value,
            'verified' => true,
        ], $moderator)
            ->assertSuccessful();

        $artist->refresh();
        self::assertTrue($artist->verified);
    }

    #[Test]
    public function moderatorCanVerifyUsersInOtherOrganizations(): void
    {
        $moderator = create_moderator();
        $otherOrg = \App\Models\Organization::factory()->create();
        $otherArtist = create_artist(['organization_id' => $otherOrg->id]);

        self::assertTrue($moderator->canVerify($otherArtist));

        $this->patchAs("api/users/{$otherArtist->public_id}", [
            'name' => $otherArtist->name,
            'email' => $otherArtist->email,
            'role' => $otherArtist->role->value,
            'verified' => true,
        ], $moderator)
            ->assertSuccessful();
    }

    #[Test]
    public function verifiedManagerCanVerifyTheirArtists(): void
    {
        $manager = create_manager();
        $manager->update(['verified' => true]);
        $artist = create_artist();

        // Assign artist to manager
        $manager->managedArtists()->attach($artist);

        self::assertTrue($manager->isVerified());
        self::assertTrue($manager->canVerify($artist));

        $this->patchAs("api/users/{$artist->public_id}", [
            'name' => $artist->name,
            'email' => $artist->email,
            'role' => $artist->role->value,
            'verified' => true,
        ], $manager)
            ->assertSuccessful();

        $artist->refresh();
        self::assertTrue($artist->verified);
    }

    #[Test]
    public function unverifiedManagerCannotVerifyAnyArtist(): void
    {
        $manager = create_manager();
        $manager->update(['verified' => false]);
        $artist = create_artist();

        // Assign artist to manager
        $manager->managedArtists()->attach($artist);

        self::assertFalse($manager->isVerified());
        self::assertFalse($manager->canVerify($artist));

        $this->patchAs("api/users/{$artist->public_id}", [
            'name' => $artist->name,
            'email' => $artist->email,
            'role' => $artist->role->value,
            'verified' => true,
        ], $manager)
            ->assertForbidden();

        $artist->refresh();
        self::assertFalse($artist->verified);
    }

    #[Test]
    public function verifiedManagerCanOnlyVerifyTheirOwnArtists(): void
    {
        $manager = create_manager();
        $manager->update(['verified' => true]);
        $ownArtist = create_artist();
        $otherArtist = create_artist();

        // Assign only ownArtist to manager
        $manager->managedArtists()->attach($ownArtist);

        self::assertTrue($manager->canVerify($ownArtist));
        self::assertFalse($manager->canVerify($otherArtist));

        // Should succeed for own artist
        $this->patchAs("api/users/{$ownArtist->public_id}", [
            'name' => $ownArtist->name,
            'email' => $ownArtist->email,
            'role' => $ownArtist->role->value,
            'verified' => true,
        ], $manager)
            ->assertSuccessful();

        // Should fail for other artist
        $this->patchAs("api/users/{$otherArtist->public_id}", [
            'name' => $otherArtist->name,
            'email' => $otherArtist->email,
            'role' => $otherArtist->role->value,
            'verified' => true,
        ], $manager)
            ->assertForbidden();
    }

    #[Test]
    public function managerCannotVerifyNonArtistUsers(): void
    {
        $manager = create_manager();
        $manager->update(['verified' => true]);
        $regularUser = create_user();

        self::assertFalse($manager->canVerify($regularUser));
    }

    #[Test]
    public function artistCannotVerifyAnyone(): void
    {
        $artist = create_artist();
        $otherArtist = create_artist();

        self::assertFalse($artist->canVerify($otherArtist));
    }

    #[Test]
    public function regularUserCannotVerifyAnyone(): void
    {
        $user = create_user();
        $artist = create_artist();

        self::assertFalse($user->canVerify($artist));
    }

    #[Test]
    public function newUsersAreNotVerifiedByDefault(): void
    {
        $admin = create_admin();

        $this->postAs('api/users', [
            'name' => 'New Artist',
            'email' => 'new@artist.com',
            'password' => 'secret',
            'role' => Role::ARTIST->value,
        ], $admin)
            ->assertSuccessful();

        $user = User::query()->where('email', 'new@artist.com')->firstOrFail();

        self::assertFalse($user->verified);
    }

    #[Test]
    public function adminCanCreateVerifiedUser(): void
    {
        $admin = create_admin();

        $this->postAs('api/users', [
            'name' => 'Verified Artist',
            'email' => 'verified@artist.com',
            'password' => 'secret',
            'role' => Role::ARTIST->value,
            'verified' => true,
        ], $admin)
            ->assertSuccessful();

        $user = User::query()->where('email', 'verified@artist.com')->firstOrFail();

        self::assertTrue($user->verified);
    }

    #[Test]
    public function unverifiedManagerCannotCreateVerifiedArtist(): void
    {
        $manager = create_manager();
        $manager->update(['verified' => false]);

        self::assertFalse($manager->isVerified());

        $this->postAs('api/users', [
            'name' => 'Verified Artist',
            'email' => 'verified@artist.com',
            'password' => 'secret',
            'role' => Role::ARTIST->value,
            'verified' => true,
        ], $manager)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['verified']);
    }

    #[Test]
    public function managerCreatingArtistCreatesManagerArtistRelationship(): void
    {
        $manager = create_manager();

        $response = $this->postAs('api/users', [
            'name' => 'New Artist',
            'email' => 'new-artist@test.com',
            'password' => 'SecurePassword123!',
            'role' => Role::ARTIST->value,
        ], $manager)
            ->assertSuccessful();

        $newArtist = User::query()->where('email', 'new-artist@test.com')->firstOrFail();

        // Verify the relationship was created
        self::assertTrue($manager->managedArtists()->where('artist_id', $newArtist->id)->exists());
        self::assertTrue($newArtist->managers()->where('manager_id', $manager->id)->exists());
    }

    #[Test]
    public function unverifiedManagerCanCreateUnverifiedArtist(): void
    {
        $unverifiedManager = create_manager();
        $unverifiedManager->update(['verified' => false]);

        // Unverified manager can create unverified artists
        $this->postAs('api/users', [
            'name' => 'Unverified Artist',
            'email' => 'unverified@artist.com',
            'password' => 'SecurePassword123!',
            'role' => Role::ARTIST->value,
            'verified' => false,
        ], $unverifiedManager)
            ->assertSuccessful();

        $artist = User::query()->where('email', 'unverified@artist.com')->firstOrFail();
        self::assertFalse($artist->verified);
    }

    #[Test]
    public function managerCanEditThemselves(): void
    {
        $manager = create_manager();
        $admin = create_admin();

        // Admin updates manager's info (to bypass role validation)
        $this->patchAs("api/users/{$manager->public_id}", [
            'name' => 'Updated Manager Name',
            'email' => $manager->email,
            'role' => Role::MANAGER->value,
        ], $admin)
            ->assertSuccessful();

        $manager->refresh();
        self::assertSame('Updated Manager Name', $manager->name);

        // Now test that manager can edit themself (just name/email)
        $this->patchAs("api/users/{$manager->public_id}", [
            'name' => 'Manager Self Edit',
            'email' => $manager->email,
            'role' => Role::MANAGER->value,
        ], $manager)
            ->assertSuccessful();

        $manager->refresh();
        self::assertSame('Manager Self Edit', $manager->name);
    }
}
