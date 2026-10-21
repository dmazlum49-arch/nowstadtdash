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
        Schema::create('business_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->integer('rating')->comment('1-5 stars');
            $table->text('review')->nullable();
            
            // Moderation fields
            $table->string('moderation_status')->default('pending')->comment('pending, flagged, approved, rejected');
            $table->json('moderation_details')->nullable();
            $table->timestamp('ai_flagged_at')->nullable();
            $table->string('status')->default('active');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Unique constraint: one user can rate a business only once
            $table->unique(['user_id', 'business_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_ratings');
    }
};
