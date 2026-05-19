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
        Schema::table('roulettes', function (Blueprint $table) {
            $table->dropUnique('roulettes_tag_fingerprint_unique');
        });
    }

    public function down(): void
    {
        Schema::table('roulettes', function (Blueprint $table) {
            $table->unique('tag_fingerprint');
        });
    }
};
