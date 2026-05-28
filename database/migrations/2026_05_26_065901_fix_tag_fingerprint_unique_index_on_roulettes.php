<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate (tag_fingerprint, media_type) rows, keeping the lowest id.
        DB::statement('
            DELETE r1 FROM roulettes r1
            INNER JOIN roulettes r2
                ON r1.tag_fingerprint = r2.tag_fingerprint
               AND r1.media_type      = r2.media_type
               AND r1.id              > r2.id
        ');

        Schema::table('roulettes', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM roulettes'))->pluck('Key_name');
            if ($indexes->contains('roulettes_tag_fingerprint_unique')) {
                $table->dropUnique('roulettes_tag_fingerprint_unique');
            }
            if (!$indexes->contains('roulettes_tag_fingerprint_media_type_unique')) {
                $table->unique(['tag_fingerprint', 'media_type']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('roulettes', function (Blueprint $table) {
            $table->dropUnique(['tag_fingerprint', 'media_type']);
            $table->unique('tag_fingerprint');
        });
    }
};