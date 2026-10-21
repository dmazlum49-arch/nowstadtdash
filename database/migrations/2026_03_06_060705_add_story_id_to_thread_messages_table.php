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
        Schema::table('thread_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('story_id')->nullable()->after('reply_to_id');
            $table->foreign('story_id')->references('id')->on('stories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thread_messages', function (Blueprint $table) {
            $table->dropForeign(['story_id']);
            $table->dropColumn('story_id');
        });
    }
};
