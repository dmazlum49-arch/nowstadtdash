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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_two_factor_authentication')->default(false)->after('is_verified');
            $table->string('google2fa_secret')->nullable()->after('is_two_factor_authentication');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_two_factor_authentication');
            $table->dropColumn('google2fa_secret');
        });
    }
};
