<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tmdb_request_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('endpoint', 80);
            $table->boolean('cached');
            $table->smallInteger('status_code')->nullable();
            $table->smallInteger('response_time_ms')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('visitor_hash', 16)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['created_at', 'endpoint']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tmdb_request_logs');
    }
};
