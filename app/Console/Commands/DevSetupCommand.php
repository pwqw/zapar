<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DevSetupCommand extends Command
{
    protected $signature = 'dev:setup';

    protected $description = 'Setup development environment: migrate fresh, init, and seed with sample data';

    public function handle(): int
    {
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
