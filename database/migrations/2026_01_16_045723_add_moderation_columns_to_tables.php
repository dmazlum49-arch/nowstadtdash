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
        Schema::table('posts', function (Blueprint $table) {
            $table->enum('moderation_status', ['pending', 'approved', 'flagged', 'rejected'])->default('pending')->after('status');
            $table->json('moderation_details')->nullable()->after('moderation_status');
            $table->timestamp('ai_flagged_at')->nullable()->after('moderation_details');
        });

        Schema::table('post_comments', function (Blueprint $table) {
            $table->enum('moderation_status', ['pending', 'approved', 'flagged', 'rejected'])->default('pending')->after('comment');
            $table->json('moderation_details')->nullable()->after('moderation_status');
            $table->timestamp('ai_flagged_at')->nullable()->after('moderation_details');
        });

        Schema::table('thread_messages', function (Blueprint $table) {
            $table->enum('moderation_status', ['pending', 'approved', 'flagged', 'rejected'])->default('pending')->after('message');
            $table->json('moderation_details')->nullable()->after('moderation_status');
            $table->timestamp('ai_flagged_at')->nullable()->after('moderation_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['moderation_status', 'moderation_details', 'ai_flagged_at']);
        });

        Schema::table('post_comments', function (Blueprint $table) {
            $table->dropColumn(['moderation_status', 'moderation_details', 'ai_flagged_at']);
        });

        Schema::table('thread_messages', function (Blueprint $table) {
            $table->dropColumn(['moderation_status', 'moderation_details', 'ai_flagged_at']);
        });
    }
};
