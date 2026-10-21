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
        Schema::table('qr_scan_logs', function (Blueprint $table) {
            $table->string('type')->default('qr')->after('business_id');
            $table->string('code', 10)->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qr_scan_logs', function (Blueprint $table) {
            $table->dropColumn(['type', 'code']);
        });
    }
};
