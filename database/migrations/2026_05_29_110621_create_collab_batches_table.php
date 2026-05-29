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
        Schema::create('collab_batches', function (Blueprint $table) {
            $table->string('token', 8)->primary();
            $table->json('movies');
            $table->string('media_type', 10)->default('movie');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collab_batches');
    }
};
