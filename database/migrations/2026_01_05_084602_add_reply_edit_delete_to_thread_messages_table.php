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
            $table->unsignedBigInteger('reply_to_id')->nullable()->default(0)->after('id');
            $table->boolean('is_edited')->default(false)->after('reply_to_id');
            $table->boolean('is_deleted')->default(false)->after('is_edited');
            $table->index('reply_to_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thread_messages', function (Blueprint $table) {
            $table->dropColumn(['reply_to_id', 'is_edited', 'is_deleted']);
        });
    }
};
