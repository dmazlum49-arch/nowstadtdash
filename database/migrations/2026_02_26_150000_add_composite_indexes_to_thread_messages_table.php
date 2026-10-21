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
            $table->index(['thread_id', 'receiver_id', 'receiver_status'], 'tm_thread_receiver_status_index');
            $table->index(['thread_id', 'receiver_id', 'sender_status'], 'tm_thread_sender_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thread_messages', function (Blueprint $table) {
            $table->dropIndex('tm_thread_receiver_status_index');
            $table->dropIndex('tm_thread_sender_status_index');
        });
    }
};
