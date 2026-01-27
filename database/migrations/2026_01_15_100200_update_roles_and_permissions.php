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

        // Create new permissions for the API guard (matches config auth.defaults.guard)
        $allPermissions = [];
        foreach (Permission::cases() as $permission) {
            $allPermissions[] = PermissionModel::findOrCreate($permission->value, self::GUARD_API);
        }

        // Get existing roles (they already exist from previous migration)
        $adminRole = RoleModel::findOrCreate(Role::ADMIN->value, self::GUARD_API);
        $moderatorRole = RoleModel::findOrCreate(Role::MODERATOR->value, self::GUARD_API);
        $managerRole = RoleModel::findOrCreate(Role::MANAGER->value, self::GUARD_API);
        $artistRole = RoleModel::findOrCreate(Role::ARTIST->value, self::GUARD_API);
        $userRole = RoleModel::findOrCreate(Role::USER->value, self::GUARD_API);

        // ADMIN: all permissions (use model instances to avoid findByName during migration)
        $adminRole->syncPermissions($allPermissions);

        $byName = static fn (string $name): ?PermissionModel => collect($allPermissions)->firstWhere('name', $name);

        // MODERATOR: manage org users, upload content, publish content
        $moderatorRole->syncPermissions(array_filter([
            $byName(Permission::MANAGE_ORG_USERS->value),
            $byName(Permission::UPLOAD_CONTENT->value),
            $byName(Permission::PUBLISH_CONTENT->value),
        ]));

        // MANAGER: manage artists, upload content
        $managerRole->syncPermissions(array_filter([
            $byName(Permission::MANAGE_ARTISTS->value),
            $byName(Permission::UPLOAD_CONTENT->value),
        ]));

        // ARTIST: upload content
        $artistRole->syncPermissions(array_filter([
            $byName(Permission::UPLOAD_CONTENT->value),
        ]));

        // USER: no permissions
        $userRole->syncPermissions([]);
    }

    public function down(): void
    {
        // Note: This is a destructive operation, ideally you would preserve the state,
        // but for a refactor this is acceptable
    }
};
