<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('help_users', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted'])->default('pending')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('help_users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
