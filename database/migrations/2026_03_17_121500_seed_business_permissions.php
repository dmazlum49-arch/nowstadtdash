<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'business-list',
            'service-list',
            'nowis-point-list',
            'stamp-card-list',
            'my-event-list',
            'my-post-list',
            'notification-list',
            'sub-user-list',
            'role-list',
            'two-factor-auth',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't necessarily want to delete them on rollback as they might be used by roles
    }
};
