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
        Schema::create('version_histories', function (Blueprint $table) {
            $table->id();
            $table->string('version')->nullable();
            $table->enum('type', ['android', 'ios'])->default('android');
            $table->enum('is_force', ['no', 'yes'])->default('yes');
            $table->string('app_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('version_histories');
    }
};
