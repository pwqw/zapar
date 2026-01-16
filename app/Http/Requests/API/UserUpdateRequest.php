<?php

namespace App\Http\Requests\API;

use App\Enums\Acl\Role;
use App\Models\User;
use App\Rules\AvailableRole;
use App\Rules\UserCanManageRole;
use App\Values\User\UserUpdateData;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * @property-read string $password
 * @property-read string $name
 * @property-read string $email
 * @property-read bool|null $verified
 */
class UserUpdateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $target->id,
            'password' => ['sometimes', Password::defaults()],
            'role' => [
                'required',
                Rule::enum(Role::class),
                new AvailableRole(),
                new UserCanManageRole($this->user()),
            ],
            'verified' => ['sometimes', 'boolean'],
        ];
    }

    protected function passedValidation(): void
    {
        // Check if verified field is being updated
        if ($this->has('verified')) {
            /** @var User $target */
            $target = $this->route('user');

            // Check authorization to verify
            Gate::authorize('verify', [User::class, $target]);
        }
    }

    public function toDto(): UserUpdateData
    {
        return UserUpdateData::make(
            name: $this->name,
            email: $this->email,
            plainTextPassword: $this->password,
            role: $this->enum('role', Role::class),
            verified: $this->has('verified') ? $this->boolean('verified') : null,
        );
    }
}
