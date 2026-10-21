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
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type')->default('image')->comment('image, video, text');
            $table->string('media_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->text('text_content')->nullable();
            $table->string('background_color')->nullable();
            $table->json('metadata')->nullable()->comment('stickers, location, etc');
            $table->string('privacy')->default('public');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });     
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
