<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watchlist', function (Blueprint $table) {
            $table->decimal('vote_average', 4, 1)->nullable()->after('genres');
        });
    }

    public function down(): void
    {
        Schema::table('watchlist', function (Blueprint $table) {
            $table->dropColumn('vote_average');
        });
    }
};