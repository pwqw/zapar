<?php

namespace Tests\Feature;

use App\Enums\Acl\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user_prospect;

class UserProspectTest extends TestCase
{
    #[Test]
    public function adminCannotUpdateProspect(): void
    {
        /** @var User $prospect */
        $prospect = create_user_prospect();

        $this->patchAs("api/users/{$prospect->public_id}", [
            'name' => 'Updated Prospect',
            'email' => $prospect->email,
            'role' => Role::USER->value,
        ], create_admin())
            ->assertForbidden();
    }

    #[Test]
    public function adminCannotVerifyProspect(): void
    {
        /** @var User $prospect */
        $prospect = create_user_prospect();

        $this->patchAs("api/users/{$prospect->public_id}", [
            'name' => $prospect->name,
            'email' => $prospect->email,
            'role' => Role::USER->value,
            'verified' => true,
        ], create_admin())
            ->assertForbidden();
    }
}
