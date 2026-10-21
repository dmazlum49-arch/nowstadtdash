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
        Schema::table('stories', function (Blueprint $table) {
            $table->string('hls_path')->nullable()->after('thumbnail_path');
            $table->string('hls_status')->nullable()->after('hls_path');
        });

        Schema::table('thread_messages', function (Blueprint $table) {
            $table->string('hls_path')->nullable()->after('thumb_image');
            $table->string('hls_status')->nullable()->after('hls_path');
        });

        Schema::table('post_images', function (Blueprint $table) {
            $table->string('hls_path')->nullable()->after('thumbnail_path');
            $table->string('hls_status')->nullable()->after('hls_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn(['hls_path', 'hls_status']);
        });

        Schema::table('thread_messages', function (Blueprint $table) {
            $table->dropColumn(['hls_path', 'hls_status']);
        });

        Schema::table('post_images', function (Blueprint $table) {
            $table->dropColumn(['hls_path', 'hls_status']);
        });
    }
};
