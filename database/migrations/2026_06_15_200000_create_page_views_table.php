<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('visitor_hash', 16)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('bot', 50)->nullable();
            $table->string('route', 255);
            $table->string('referrer', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('visitor_hash');
            $table->index(['visitor_hash', 'created_at']);
            $table->index('bot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
