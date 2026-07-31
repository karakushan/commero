<?php

use Commero\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            Permissions::RECEIVE_ORDER_NOTIFICATIONS,
            Permissions::RECEIVE_MARKETING_LEAD_NOTIFICATIONS,
            Permissions::RECEIVE_PRODUCT_REVIEW_NOTIFICATIONS,
        ];

        $admin = Role::query()->where('name', 'admin')->where('guard_name', 'web')->first();

        foreach ($permissions as $permissionName) {
            $permission = Permission::findOrCreate($permissionName, 'web');

            if ($admin) {
                $admin->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        $permissions = [
            Permissions::RECEIVE_ORDER_NOTIFICATIONS,
            Permissions::RECEIVE_MARKETING_LEAD_NOTIFICATIONS,
            Permissions::RECEIVE_PRODUCT_REVIEW_NOTIFICATIONS,
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::query()
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if (! $permission) {
                continue;
            }

            Role::query()->where('guard_name', 'web')->get()
                ->each(fn (Role $role) => $role->revokePermissionTo($permission));

            $permission->delete();
        }
    }
};
