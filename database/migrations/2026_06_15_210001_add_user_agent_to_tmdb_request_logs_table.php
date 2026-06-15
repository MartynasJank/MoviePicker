<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tmdb_request_logs', function (Blueprint $table) {
            $table->string('user_agent', 512)->nullable()->after('bot');
        });
    }

    public function down(): void
    {
        Schema::table('tmdb_request_logs', function (Blueprint $table) {
            $table->dropColumn('user_agent');
        });
    }
};
