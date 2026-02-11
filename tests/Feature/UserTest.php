<?php

namespace Tests\Feature;

use App\Models\Organization;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_moderator;
use function Tests\create_user;

class UserTest extends TestCase
{
    #[Test]
    public function creatingManagersIsOk(): void
    {
        $this->postAs('api/users', [
            'name' => 'Manager',
            'email' => 'foo@bar.com',
            'password' => 'secret',
            'role' => 'manager',
        ], create_admin())
            ->assertSuccessful();
    }

    #[Test]
    public function updatingUsersToManagersIsOk(): void
    {
        $user = create_admin();

        $this->putAs("api/users/{$user->public_id}", [
            'name' => 'Manager',
            'email' => 'foo@bar.com',
            'role' => 'manager',
        ], create_admin())
            ->assertSuccessful();
    }

    #[Test]
    public function moderatorCanListUsersFromOtherOrganizations(): void
    {
        $moderator = create_moderator();
        $otherOrganization = Organization::factory()->create();
        $otherOrganizationUser = create_user(['organization_id' => $otherOrganization->id]);

        $response = $this->getAs('api/users', $moderator)
            ->assertSuccessful();

        $publicIds = collect($response->json())->pluck('id')->all();

        self::assertContains($otherOrganizationUser->public_id, $publicIds);
    }
}
