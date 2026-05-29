<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tmdb_request_logs', 'route')) {
            return;
        }

        Schema::table('tmdb_request_logs', function (Blueprint $table) {
            $table->string('route', 60)->nullable()->after('endpoint');
        });
    }

    public function down(): void
    {
        Schema::table('tmdb_request_logs', function (Blueprint $table) {
            $table->dropColumn('route');
        });
    }
};
