<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('story_poll_votes', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('story_id')->constrained()->cascadeOnDelete();
            $blueprint->foreignId('poll_option_id')->constrained('story_poll_options')->cascadeOnDelete();
            $blueprint->foreignId('user_id')->constrained()->cascadeOnDelete();
            $blueprint->timestamps();

            $blueprint->unique(['story_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_poll_votes');
    }
};
