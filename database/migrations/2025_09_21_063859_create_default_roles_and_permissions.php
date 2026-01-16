<?php

use App\Enums\Acl\Permission;
use App\Enums\Acl\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

return new class extends Migration {
    public function up(): void
    {
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value);
        }

        RoleModel::findOrCreate(Role::ADMIN->value)
            ->givePermissionTo(Permission::cases());

        RoleModel::findOrCreate(Role::MODERATOR->value)
            ->givePermissionTo([
                Permission::MANAGE_ORG_USERS,
                Permission::UPLOAD_CONTENT,
                Permission::PUBLISH_CONTENT,
            ]);

        RoleModel::findOrCreate(Role::MANAGER->value)
            ->givePermissionTo([
                Permission::MANAGE_ARTISTS,
                Permission::UPLOAD_CONTENT,
            ]);

        RoleModel::findOrCreate(Role::ARTIST->value)
            ->givePermissionTo([
                Permission::UPLOAD_CONTENT,
            ]);

        RoleModel::findOrCreate(Role::USER->value);
    }
};
