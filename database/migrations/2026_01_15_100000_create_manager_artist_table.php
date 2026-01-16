<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manager_artist', static function (Blueprint $table): void {
            $table->unsignedInteger('manager_id');
            $table->unsignedInteger('artist_id');
            $table->timestamps();

            $table->foreign('manager_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('artist_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['manager_id', 'artist_id']);
            $table->index(['artist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_artist');
    }
};
