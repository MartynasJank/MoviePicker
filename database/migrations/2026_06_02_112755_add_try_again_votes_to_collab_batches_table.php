<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('collab_batches', function (Blueprint $table) {
            $table->json('try_again_votes')->nullable()->after('refresh_votes');
        });
    }

    public function down(): void
    {
        Schema::table('collab_batches', function (Blueprint $table) {
            $table->dropColumn('try_again_votes');
        });
    }
};
