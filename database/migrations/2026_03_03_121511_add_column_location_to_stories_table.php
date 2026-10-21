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
            $table->string('location')->nullable()->after('metadata');
            $table->string('latitude')->nullable()->after('location');
            $table->string('longitude')->nullable()->after('latitude');
            $table->json('count_down')->nullable()->after('longitude');
            $table->string('link')->nullable()->after('count_down');
            $table->string('question')->nullable()->after('link');
            $table->string('poll_question')->nullable()->after('question');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn(['location', 'latitude', 'longitude', 'count_down', 'link', 'question', 'poll_question']);
        });
    }
};
