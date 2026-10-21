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
        Schema::create('thread_messages', function (Blueprint $table) {
            $table->id();
             $table->unsignedBigInteger('thread_id')->default(0);
            $table->unsignedBigInteger('sender_id')->default(0);
            $table->unsignedBigInteger('receiver_id')->default(0);
            $table->unsignedBigInteger('object_id')->default(0);
            $table->text('message')->nullable();
            $table->text('file')->nullable();
            $table->enum('type', ['message', 'file','location','multi_file'])->default('message');
            $table->string('file_type')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->enum('status', ['read', 'unread'])->default('unread');
            $table->foreign('thread_id')->references('id')->on('threads')->cascadeOnDelete();
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('receiver_id')->references('id')->on('users')->cascadeOnDelete();
            $table->enum('sender_msg_status', ['y', 'n'])->default('y');
            $table->enum('receiver_msg_status', ['y', 'n'])->default('y');
            $table->enum('sender_status', ['y', 'n'])->default('y');
            $table->enum('receiver_status', ['y', 'n'])->default('y');
            $table->enum('view_once', ['yes', 'no'])->default('no');
            $table->timestamp('viewed_at')->nullable();
            $table->boolean('is_viewed')->default(false);
            $table->enum('delivery_status', ['sent', 'delivered', 'seen'])->default('sent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thread_messages');
    }
};
