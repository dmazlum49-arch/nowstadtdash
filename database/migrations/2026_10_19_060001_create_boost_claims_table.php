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
        Schema::create('boost_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boost_id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('multiplier')->default(2);
            $table->unsignedInteger('points_awarded')->default(20);
            $table->unsignedBigInteger('qr_scan_log_id')->nullable();
            $table->timestamps();

            $table->foreign('boost_id')->references('id')->on('boosts')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('qr_scan_log_id')->references('id')->on('qr_scan_logs')->onDelete('set null');

            // Each user can use a specific business boost only once per boost campaign
            $table->unique(['user_id', 'boost_id']);
            $table->index(['user_id', 'business_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boost_claims');
    }
};
