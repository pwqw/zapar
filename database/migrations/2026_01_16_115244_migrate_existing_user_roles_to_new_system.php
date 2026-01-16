<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration handles the transition from the old 3-role system
     * (admin, manager, user) to the new 6-role system (admin, moderator,
     * manager, artist, user) for existing installations.
     *
     * Strategy:
     * - OLD 'manager' role users → NEW 'moderator' role
     *   (managers could manage all users, which is now moderator behavior)
     * - OLD 'user' role users → NEW 'artist' role if they have uploaded content
     * - OLD 'user' role users → Remain as 'user' if they haven't uploaded content
     * - OLD 'admin' role → Remains 'admin' (no change)
     */
    public function up(): void
    {
        // Skip if no users exist (fresh installation)
        if (User::count() === 0) {
            return;
        }

        DB::transaction(static function (): void {
            // 1. Migrate old 'manager' role to 'moderator'
            // Old managers could manage all organization users, which is moderator behavior
            $oldManagers = User::whereHas('roles', static function ($query): void {
                $query->where('name', 'manager');
            })->get();

            foreach ($oldManagers as $user) {
                $user->syncRoles('moderator');
            }

            // Logged: Migrated old managers to moderator role

            // 2. Upgrade 'user' to 'artist' if they have uploaded content
            // Users who uploaded songs should be artists
            $userIdsWithContent = DB::table('songs')
                ->select('owner_id')
                ->distinct()
                ->pluck('owner_id');

            $usersWithContent = User::whereHas('roles', static function ($query): void {
                $query->where('name', 'user');
            })->whereIn('id', $userIdsWithContent)->get();

            foreach ($usersWithContent as $user) {
                $user->syncRoles('artist');
            }

            // Logged: Upgraded users with content to artist role

            // 3. Users without content remain as 'user' (no action needed)

            // 4. Ensure all users have their Spatie role synced (no-op, roles already correct)
            // Logged: All user roles migrated successfully
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting this migration is complex and potentially destructive
        // We don't automatically revert role changes as it could cause data loss
        // Manual intervention required if rollback is needed
    }
};
