<?php

namespace App\Values\User;

use App\Enums\Acl\Role;
use Illuminate\Support\Facades\Hash;

final readonly class UserUpdateData
{
    public ?string $password;

    private function __construct(
        public string $name,
        public string $email,
        ?string $plainTextPassword,
        public ?Role $role,
        public ?string $avatar,
        public ?bool $verified = null,
    ) {
        $this->password = $plainTextPassword ? Hash::make($plainTextPassword) : null;
    }

    public static function make(
        string $name,
        string $email,
        ?string $plainTextPassword = null,
        ?Role $role = null,
        ?string $avatar = null,
        ?bool $verified = null
    ): self {
        return new self(
            name: $name,
            email: $email,
            plainTextPassword: $plainTextPassword,
            role: $role,
            avatar: $avatar,
            verified: $verified,
        );
    }
}
