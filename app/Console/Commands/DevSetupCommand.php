<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DevSetupCommand extends Command
{
    protected $signature = 'dev:setup {--force : Force execution without confirmation}';

    protected $description = 'Setup development environment: migrate fresh, init, and seed with sample data';

    public function handle(): int
    {
        // ⚠️ WARNING: This command DELETES THE ENTIRE DATABASE
        if (!$this->option('force')) {
            $this->components->error('⚠️  WARNING: This command will WIPE THE ENTIRE DATABASE');
            $this->newLine();
            $this->components->warn('This will remove all users, songs, albums, and other data.');
            $this->newLine();

            if (!$this->confirm('Are you sure you want to continue?', false)) {
                $this->components->info('Cancelled.');
                return self::FAILURE;
            }
        }

        $this->info('🔧 Setting up development environment...');
        $this->newLine();

        // 1. Migrate fresh
        $this->info('1️⃣ Resetting database...');
        $this->call('migrate:fresh', ['--force' => true]);
        $this->newLine();

        // 2. Koel init
        $this->info('2️⃣ Running Koel initialization...');
        $this->call('koel:init', [
            '--no-assets' => true,
            '--no-interaction' => true,
            '--no-scheduler' => true,
        ]);
        $this->newLine();

        // 3. DevSampleDataSeeder
        $this->info('3️⃣ Seeding sample data...');
        $this->call('db:seed', ['--class' => 'DevSampleDataSeeder']);
        $this->newLine();

        $this->info('✅ Development environment setup completed!');

        return self::SUCCESS;
    }
}
