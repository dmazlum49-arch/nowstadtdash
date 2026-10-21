<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'user-list',
            'business-user-list',
            'sub-admin-list',
            'business-category-list',
            'business-list',
            'event-category-list',
            'event-list',
            'banner-list',
            'city-list',
            'service-list',
            'qr-change-list',
            'boost-list',
            'my-event-list',
            'my-post-list',
            'notification-list',
            'report-type-list',
            'report-list',
            'moderation-list',
            'content-list',
            'reported-content-list',
            'business-post-permission-list',
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            'setting-list',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Create roles and assign permissions
        $role = Role::findOrCreate('Super Admin');
        $role->givePermissionTo(Permission::all());

        // Assign to first admin user if exists
        $user = User::where('type', 'admin')->first();
        if ($user) {
            $user->assignRole($role);
        }
    }
}
