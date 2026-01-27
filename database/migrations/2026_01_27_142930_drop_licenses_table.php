<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('licenses');
        Cache::forget('license_status');
    }

    public function down(): void
    {
        // No rollback - licensing system has been permanently removed
    }
};
