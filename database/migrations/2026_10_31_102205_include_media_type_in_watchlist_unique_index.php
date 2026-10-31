<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watchlist', function (Blueprint $table) {
            $table->unique(['user_id', 'tmdb_id', 'type']);
        });
        Schema::table('watchlist', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'tmdb_id']);
        });
    }

    public function down(): void
    {
        if (DB::table('watchlist')->select('user_id', 'tmdb_id')
            ->groupBy('user_id', 'tmdb_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot roll back while movie and TV entries share a TMDB ID. No watchlist data was removed.');
        }

        Schema::table('watchlist', function (Blueprint $table) {
            $table->unique(['user_id', 'tmdb_id']);
        });
        Schema::table('watchlist', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'tmdb_id', 'type']);
        });
    }
};
