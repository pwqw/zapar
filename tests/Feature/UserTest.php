<?php

namespace Tests\Feature;

use App\Enums\Acl\Role;
use App\Helpers\Ulid;
use App\Models\Interaction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_artist;
use function Tests\create_manager;
use function Tests\create_moderator;
use function Tests\create_user;

class UserTest extends TestCase
{
    #[Test]
    public function nonAdminCannotCreateUser(): void
    {
        // Create a regular user (no manage permissions)
        $user = create_user();

        // Regular users cannot create users – they lack manage permission → 403
        $this->postAs('api/users', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'secret',
            'role' => Role::USER->value,
        ], $user)
            ->assertForbidden();
    }

    #[Test]
    public function adminCreatesUser(): void
    {
        $this->postAs('api/users', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'secret',
            'role' => Role::USER->value,
        ], create_admin())
            ->assertSuccessful();

        /** @var User $user */
        $user = User::query()->firstWhere('email', 'bar@baz.com');

        self::assertTrue(Hash::check('secret', $user->password));
        self::assertSame('Foo', $user->name);
        self::assertSame('bar@baz.com', $user->email);
        self::assertSame(Role::USER, $user->role);
    }

    #[Test]
    public function adminCanCreateUsersWithLowerRoles(): void
    {
        $admin = create_admin();

        // Roles that are lower than ADMIN
        $lowerRoles = [Role::MODERATOR, Role::MANAGER, Role::ARTIST, Role::USER];

        foreach ($lowerRoles as $role) {
            $response = $this->postAs('api/users', [
                'name' => 'Test User',
                'email' => "user-{$role->value}@test.com",
                'password' => 'secret',
                'role' => $role->value,
            ], $admin);

            $response->assertSuccessful();
        }
    }

    #[Test]
    public function adminCanCreateAnotherAdmin(): void
    {
        $admin = create_admin();

        // Same rule as invitation: admin can create/invite same level (admin→admin)
        $this->postAs('api/users', [
            'name' => 'Another Admin',
            'email' => 'another-admin@test.com',
            'password' => 'secret',
            'role' => Role::ADMIN->value,
        ], $admin)
            ->assertSuccessful();

        $created = User::query()->firstWhere('email', 'another-admin@test.com');
        self::assertSame(Role::ADMIN, $created->role);
    }

    #[Test]
    public function privilegeEscalationIsForbiddenWhenCreating(): void
    {
        $this->postAs('api/users', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'secret',
            'role' => 'admin',
        ], create_manager())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    #[Test]
    public function creatingUsersWithHigherRoleIsNotAllowed(): void
    {
        $admin = create_admin();

        $this->putAs("api/users/{$admin->public_id}", [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'new-secret',
            'role' => 'user',
        ], create_manager())
            ->assertForbidden();
    }

    #[Test]
    public function adminUpdatesUser(): void
    {
        $admin = create_admin();
        $user = create_admin(['password' => 'secret']);

        $this->putAs("api/users/{$user->public_id}", [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'new-secret',
            'role' => 'user',
        ], $admin)
            ->assertSuccessful();

        $user->refresh();

        self::assertTrue(Hash::check('new-secret', $user->password));
        self::assertSame('Foo', $user->name);
        self::assertSame('bar@baz.com', $user->email);
        self::assertSame(Role::USER, $user->role);
    }

    #[Test]
    public function privilegeEscalationIsForbiddenWhenUpdating(): void
    {
        $manager = create_manager();

        $this->putAs("api/users/{$manager->public_id}", [
            'role' => 'admin',
        ], create_manager())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    #[Test]
    public function managerCanUpdateManagedArtist(): void
    {
        $artist = create_artist();
        $manager = create_manager();
        $manager->managedArtists()->attach($artist);

        $this->putAs("api/users/{$artist->public_id}", [
            'name' => $artist->name,
            'email' => $artist->email,
            'role' => Role::ARTIST->value,
        ], $manager)
            ->assertSuccessful();

        $artist->refresh();
        self::assertSame(Role::ARTIST, $artist->role);
    }

        #[Test]
    public function adminDeletesUser(): void
    {
        $user = create_user();

        $this->deleteAs("api/users/{$user->public_id}", [], create_admin());
        $this->assertModelMissing($user);
    }

    #[Test]
    public function selfDeletionNotAllowed(): void
    {
        $admin = create_admin();

        $this->deleteAs("api/users/{$admin->public_id}", [], $admin)->assertForbidden();
        $this->assertModelExists($admin);
    }

    #[Test]
    public function pruneOldDemoAccounts(): void
    {
        config(['koel.misc.demo' => true]);

        $oldUserWithNoActivity = create_user([
            'created_at' => now()->subDays(30),
            'email' => Ulid::generate() . '@demo.koel.dev',
        ]);

        $oldUserWithOldActivity = create_user([
            'created_at' => now()->subDays(30),
            'email' => Ulid::generate() . '@demo.koel.dev',
        ]);

        Interaction::factory()->for($oldUserWithOldActivity)->create([
            'last_played_at' => now()->subDays(14),
        ]);

        $oldUserWithNonDemoEmail = create_user([
            'created_at' => now()->subDays(30),
            'email' => Ulid::generate() . '@example.com',
        ]);

        $oldUserWithNewActivity = create_user([
            'created_at' => now()->subDays(30),
            'email' => Ulid::generate() . '@demo.koel.dev',
        ]);

        Interaction::factory()->for($oldUserWithNewActivity)->create([
            'last_played_at' => now()->subDays(6),
        ]);

        $newUser = create_user([
            'created_at' => now()->subDay(),
            'email' => Ulid::generate() . '@demo.koel.dev',
        ]);

        Artisan::call('model:prune');

        $this->assertModelMissing($oldUserWithNoActivity);
        $this->assertModelMissing($oldUserWithOldActivity);
        $this->assertModelExists($oldUserWithNonDemoEmail);
        $this->assertModelExists($oldUserWithNewActivity);
        $this->assertModelExists($newUser);

        config(['koel.misc.demo' => false]);
    }

    #[Test]
    public function noPruneIfNotInDemoMode(): void
    {
        $user = create_user([
            'created_at' => now()->subDays(30),
            'email' => Ulid::generate() . '@demo.koel.dev',
        ]);

        Artisan::call('model:prune');

        $this->assertModelExists($user);
    }

    #[Test]
    public function managerCanListManagedArtists(): void
    {
        $manager = create_manager();
        $artist1 = create_artist();
        $artist2 = create_artist();
        $otherArtist = create_artist();

        // Assign artists to manager
        $manager->managedArtists()->sync([$artist1->id, $artist2->id]);

        $response = $this->getAs('api/users', $manager);

        // Manager should be able to list users
        $response->assertSuccessful();

        $json = $response->json();
        // UserResource collection uses 'data' wrapper
        $data = $json;

        // Manager should see only their 2 managed artists
        $this->assertCount(2, $data);

        $userPublicIds = collect($data)->pluck('id')->toArray();

        $this->assertContains($artist1->public_id, $userPublicIds);
        $this->assertContains($artist2->public_id, $userPublicIds);
        $this->assertNotContains($otherArtist->public_id, $userPublicIds);
    }

    #[Test]
    public function managerCannotListNonManagedArtists(): void
    {
        $manager = create_manager();
        $artist = create_artist();
        // Don't assign the artist to the manager

        $response = $this->getAs('api/users', $manager)
            ->assertSuccessful();

        $data = $response->json();
        $userPublicIds = collect($data)->pluck('id')->toArray();

        // Manager should not see non-managed artists
        $this->assertNotContains($artist->public_id, $userPublicIds);
    }

    #[Test]
    public function moderatorCanListOrgUsers(): void
    {
        $moderator = create_moderator();
        $userInOrg = create_user(['organization_id' => $moderator->organization_id]);

        // Create a different organization and a user in it
        $otherOrg = Organization::factory()->create();
        $userOutsideOrg = create_user(['organization_id' => $otherOrg->id]);

        $response = $this->getAs('api/users', $moderator)
            ->assertSuccessful();

        $data = $response->json();
        $userPublicIds = collect($data)->pluck('id')->toArray();

        // Moderator should see at least the user in their org (and themselves)
        $this->assertContains($moderator->public_id, $userPublicIds);
        $this->assertContains($userInOrg->public_id, $userPublicIds);
        $this->assertNotContains($userOutsideOrg->public_id, $userPublicIds);
    }
}
