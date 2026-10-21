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
        Schema::create('help_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('help_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->longText('comment');
            $table->enum('moderation_status', ['pending', 'approved','flagged', 'rejected'])->default('pending');
            $table->json('moderation_details')->nullable();
            $table->timestamp('ai_flagged_at')->nullable();
            $table->enum('status', ['active', 'inactive','banned'])->default('active');
            $table->timestamps();
            $table->foreign('help_id')->references('id')->on('helps')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_comments');
    }
};
