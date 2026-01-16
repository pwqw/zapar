<?php

use App\Enums\Acl\Permission;
use App\Enums\Acl\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

return new class extends Migration {
    public function up(): void
    {
        // Create new permissions
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value);
        }

        // Get existing roles (they already exist from previous migration)
        $adminRole = RoleModel::findOrCreate(Role::ADMIN->value);
        $moderatorRole = RoleModel::findOrCreate(Role::MODERATOR->value);
        $managerRole = RoleModel::findOrCreate(Role::MANAGER->value);
        $artistRole = RoleModel::findOrCreate(Role::ARTIST->value);
        $userRole = RoleModel::findOrCreate(Role::USER->value);

        // ADMIN: all permissions
        $adminRole->syncPermissions(Permission::cases());

        // MODERATOR: manage org users, upload content, publish content
        $moderatorRole->syncPermissions([
            Permission::MANAGE_ORG_USERS,
            Permission::UPLOAD_CONTENT,
            Permission::PUBLISH_CONTENT,
        ]);

        // MANAGER: manage artists, upload content
        $managerRole->syncPermissions([
            Permission::MANAGE_ARTISTS,
            Permission::UPLOAD_CONTENT,
        ]);

        // ARTIST: upload content
        $artistRole->syncPermissions([
            Permission::UPLOAD_CONTENT,
        ]);

        // USER: no permissions
        $userRole->syncPermissions([]);
    }

    public function down(): void
    {
        // Note: This is a destructive operation, ideally you would preserve the state,
        // but for a refactor this is acceptable
    }
};
