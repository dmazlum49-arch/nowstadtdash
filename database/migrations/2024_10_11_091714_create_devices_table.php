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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('device_id')->unique()->index();
            $table->string('device_token')->nullable()->index();
            $table->enum('type', ['android', 'ios'])->default('android')->index();
            $table->string('app_type', 50)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('version', 10)->nullable();
            $table->string('time_zone', 100)->nullable();
            $table->string('language', 100)->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
