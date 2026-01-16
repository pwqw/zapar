<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only create demo admin if no users exist
        if (User::count() > 0) {
            return;
        }

        // Get or create default organization
        $organization = Organization::firstOrCreate(['name' => 'Koel']);

        // Create default admin user
        $admin = User::create([
            'name' => 'Koel Administrator',
            'email' => 'admin@koel.dev',
            'password' => Hash::make('KoelIsCool'),
            'organization_id' => $organization->id,
        ]);

        // Assign admin role via Spatie Permission
        $admin->assignRole('admin');

        $this->command->info('✅ Default admin user created: admin@koel.dev / KoelIsCool');
    }
}
