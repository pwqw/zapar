<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add uploaded_by_id to songs
        Schema::table('songs', static function (Blueprint $table): void {
            $table->unsignedInteger('uploaded_by_id')->nullable();
            $table->foreign('uploaded_by_id')->references('id')->on('users')->nullOnDelete();
        });

        // Backfill uploaded_by_id with owner_id for existing songs
        DB::table('songs')->whereNull('uploaded_by_id')->update([
            'uploaded_by_id' => DB::raw('owner_id'),
        ]);

        // Add uploaded_by_id to radio_stations
        Schema::table('radio_stations', static function (Blueprint $table): void {
            $table->unsignedInteger('uploaded_by_id')->nullable();
            $table->foreign('uploaded_by_id')->references('id')->on('users')->nullOnDelete();
        });

        // Backfill uploaded_by_id with user_id for existing radio_stations
        DB::table('radio_stations')->whereNull('uploaded_by_id')->update([
            'uploaded_by_id' => DB::raw('user_id'),
        ]);

        // For podcasts, we'll keep added_by as is (no changes needed)
        // Episodes already have owner_id which we can use as uploaded_by
    }

    public function down(): void
    {
        Schema::table('songs', static function (Blueprint $table): void {
            $table->dropForeign(['uploaded_by_id']);
            $table->dropColumn('uploaded_by_id');
        });

        Schema::table('radio_stations', static function (Blueprint $table): void {
            $table->dropForeign(['uploaded_by_id']);
            $table->dropColumn('uploaded_by_id');
        });
    }
};
