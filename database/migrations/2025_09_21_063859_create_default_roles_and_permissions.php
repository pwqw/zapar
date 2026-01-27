<?php

use App\Enums\Acl\Permission;
use App\Enums\Acl\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    private const GUARD_API = 'api';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [];
        foreach (Permission::cases() as $permission) {
            $permissions[] = PermissionModel::findOrCreate($permission->value, self::GUARD_API);
        }

        RoleModel::findOrCreate(Role::ADMIN->value, self::GUARD_API)
            ->givePermissionTo($permissions);

        RoleModel::findOrCreate(Role::MODERATOR->value, self::GUARD_API)
            ->givePermissionTo([
                Permission::MANAGE_ORG_USERS,
                Permission::UPLOAD_CONTENT,
                Permission::PUBLISH_CONTENT,
            ]);

        RoleModel::findOrCreate(Role::MANAGER->value, self::GUARD_API)
            ->givePermissionTo([
                Permission::MANAGE_ARTISTS,
                Permission::UPLOAD_CONTENT,
            ]);

        RoleModel::findOrCreate(Role::ARTIST->value, self::GUARD_API)
            ->givePermissionTo([
                Permission::UPLOAD_CONTENT,
            ]);

        RoleModel::findOrCreate(Role::USER->value, self::GUARD_API);
    }
};
