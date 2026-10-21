<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('story_poll_options', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('story_id')->constrained()->cascadeOnDelete();
            $blueprint->string('option_text');
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_poll_options');
    }
};
