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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->bigInteger('from_user_id')->default('0');
            $table->integer('push_type')->default('0');
            $table->string('title', 150)->nullable();
            $table->longText('message')->nullable();
            $table->boolean('read')->default(false);
            $table->string('object_id', 10)->nullable();
            $table->string('object_type', 100)->nullable();
            $table->longText('extra')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
