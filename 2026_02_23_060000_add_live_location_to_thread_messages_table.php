<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter the 'type' enum to include 'live_location' and 'album'
        DB::statement("ALTER TABLE thread_messages MODIFY COLUMN `type` ENUM('message','file','location','multi_file','live_location','album') NOT NULL DEFAULT 'message'");

        Schema::table('thread_messages', function (Blueprint $table) {
            // When the live location session expires (set by sender's duration)
            $table->timestamp('live_location_expires_at')->nullable()->after('longitude');
            // When the sender explicitly stops sharing live location
            $table->timestamp('live_location_stopped_at')->nullable()->after('live_location_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thread_messages', function (Blueprint $table) {
            $table->dropColumn(['live_location_expires_at', 'live_location_stopped_at']);
        });

        DB::statement("ALTER TABLE thread_messages MODIFY COLUMN `type` ENUM('message','file','location','multi_file','album') NOT NULL DEFAULT 'message'");
    }
};
