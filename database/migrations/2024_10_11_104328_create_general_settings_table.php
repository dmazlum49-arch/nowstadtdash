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
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('label', 150);
            $table->string('unique_name', 150);
            $table->string('input_type')->default('text');
            $table->string('value')->nullable();
            $table->string('options')->nullable();
            $table->string('class', 100)->default('form-control');
            $table->string('extra')->nullable();
            $table->string('hint')->nullable();
            $table->integer('order_number')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('type', ['general', 'version'])->default('general');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
