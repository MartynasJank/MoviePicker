<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collab_batches', function (Blueprint $table) {
            $table->json('votes')->nullable();
            $table->json('restore_votes')->nullable();
            $table->json('graveyard')->nullable();
            $table->json('ready')->nullable();
            $table->json('refresh_votes')->nullable();
            $table->json('participants')->nullable();
            $table->json('criteria')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('collab_batches', function (Blueprint $table) {
            $table->dropColumn(['votes','restore_votes','graveyard','ready','refresh_votes','participants','criteria']);
        });
    }
};
