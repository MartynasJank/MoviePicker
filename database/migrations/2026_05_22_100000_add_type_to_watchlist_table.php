<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watchlist', function (Blueprint $table) {
            $table->string('type')->default('movie')->after('vote_average');
        });
    }

    public function down(): void
    {
        Schema::table('watchlist', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
