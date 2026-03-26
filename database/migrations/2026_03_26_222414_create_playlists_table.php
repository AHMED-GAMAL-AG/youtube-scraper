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
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();
            $table->string('playlist_id', 64)->unique();
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->string('thumbnail', 500);
            $table->string('channel_name', 255);
            $table->string('category', 100)->index();
            $table->unsignedInteger('video_count')->default(0);
            $table->string('total_duration', 50)->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playlists');
    }
};
