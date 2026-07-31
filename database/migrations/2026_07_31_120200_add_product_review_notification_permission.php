<?php

use Commero\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate(
            Permissions::RECEIVE_PRODUCT_REVIEW_NOTIFICATIONS,
            'web',
        );

        Role::query()
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo($permission);
    }

    public function down(): void
    {
        $permission = Permission::query()
            ->where('name', Permissions::RECEIVE_PRODUCT_REVIEW_NOTIFICATIONS)
            ->where('guard_name', 'web')
            ->first();

        if (! $permission) {
            return;
        }

        Role::query()->where('guard_name', 'web')->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permission));

        $permission->delete();
    }
};
