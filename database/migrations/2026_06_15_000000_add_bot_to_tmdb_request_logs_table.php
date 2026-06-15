<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tmdb_request_logs', function (Blueprint $table) {
            $table->string('bot', 50)->nullable()->after('visitor_hash');
            $table->index('bot');
        });
    }

    public function down(): void
    {
        Schema::table('tmdb_request_logs', function (Blueprint $table) {
            $table->dropIndex(['bot']);
            $table->dropColumn('bot');
        });
    }
};