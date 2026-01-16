<?php

namespace App\Http\Requests\API;

use App\Enums\Acl\Role;
use App\Models\User;
use App\Rules\AvailableRole;
use App\Rules\UserCanManageRole;
use App\Values\User\UserCreateData;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * @property-read string $password
 * @property-read string $name
 * @property-read string $email
 * @property-read bool|null $verified
 */
class UserStoreRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => ['required', Password::defaults()],
            'role' => [
                'required',
                Rule::enum(Role::class),
                new AvailableRole(),
                new UserCanManageRole($this->user()),
            ],
            'verified' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Check if verified field is being set to true
            if ($this->has('verified') && $this->boolean('verified')) {
                // For store requests, we need to check if the current user can verify users of the requested role
                // Create a temporary user to check permissions
                $tempUser = new User();
                $tempUser->forceFill(['id' => 0]); // Temporary ID
                $tempUser->syncRoles($this->enum('role', Role::class));

                if (!Gate::allows('verify', [User::class, $tempUser])) {
                    $validator->errors()->add('verified', 'You are not authorized to create verified users.');
                }
            }
        });
    }

    public function toDto(): UserCreateData
    {
        return UserCreateData::make(
            name: $this->name,
            email: $this->email,
            plainTextPassword: $this->password,
            role: $this->enum('role', Role::class),
            verified: $this->boolean('verified', false),
        );
    }
}
