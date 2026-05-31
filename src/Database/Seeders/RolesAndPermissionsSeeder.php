<?php

namespace Commero\Database\Seeders;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    protected array $resources = [
        'AttributeGroup',
        'Brand',
        'Category',
        'CityCategory',
        'Currency',
        'MarketingLead',
        'Menu',
        'Order',
        'OrderStatus',
        'Page',
        'PaymentMethod',
        'Post',
        'PostCategory',
        'Product',
        'ProductAttribute',
        'ProductReview',
        'ShippingMethod',
        'User',
    ];

    /**
     * @var array<int, string>
     */
    protected array $resourceActions = [
        'ViewAny',
        'View',
        'Create',
        'Update',
        'Delete',
        'DeleteAny',
        'ForceDelete',
        'ForceDeleteAny',
        'Replicate',
        'Reorder',
        'Restore',
        'RestoreAny',
    ];

    /**
     * @var array<int, string>
     */
    protected array $pages = [];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ensurePackagePermissionsExist();

        $buyer = Role::findOrCreate('buyer', 'web');
        $manager = Role::findOrCreate('manager', 'web');
        $editor = Role::findOrCreate('editor', 'web');
        $admin = Role::findOrCreate('admin', 'web');

        $buyer->syncPermissions([]);

        $manager->syncPermissions($this->permissionsForResources(
            resources: ['Order'],
            actions: ['ViewAny', 'View', 'Update'],
        ));

        $editor->syncPermissions(
            $this->permissionsForResources(
                resources: [
                    'AttributeGroup',
                    'Brand',
                    'Category',
                    'CityCategory',
                    'Currency',
                    'Menu',
                    'OrderStatus',
                    'Page',
                    'PaymentMethod',
                    'Post',
                    'PostCategory',
                    'Product',
                    'ProductAttribute',
                    'ProductReview',
                    'ShippingMethod',
                ],
                actions: ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Replicate', 'Reorder'],
            ),
        );

        $admin->syncPermissions(Permission::query()->pluck('name')->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int, string>  $resources
     * @param  array<int, string>  $actions
     * @return array<int, string>
     */
    protected function permissionsForResources(array $resources, array $actions): array
    {
        $permissionNames = collect($resources)
            ->flatMap(fn (string $resource): array => collect($actions)
                ->map(fn (string $action): string => "{$action}:{$resource}")
                ->all())
            ->values()
            ->all();

        return Permission::query()
            ->whereIn('name', $permissionNames)
            ->pluck('name')
            ->all();
    }

    protected function ensurePackagePermissionsExist(): void
    {
        $permissions = [
            ...$this->permissionNamesForResources($this->resources, $this->resourceActions),
            ...$this->pagePermissionNames(),
        ];

        foreach (array_unique($permissions) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    /**
     * @param  array<int, string>  $resources
     * @param  array<int, string>  $actions
     * @return array<int, string>
     */
    protected function permissionNamesForResources(array $resources, array $actions): array
    {
        return collect($resources)
            ->flatMap(fn (string $resource): array => collect($actions)
                ->map(fn (string $action): string => "{$action}:{$resource}")
                ->all())
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function pagePermissionNames(): array
    {
        $pages = FilamentShield::getPages() ?? [];

        $resolved = collect($pages)
            ->filter(fn (array $page): bool => Str::startsWith((string) ($page['pageFqcn'] ?? ''), 'Commero\\'))
            ->flatMap(fn (array $page): array => array_keys((array) ($page['permissions'] ?? [])))
            ->values()
            ->all();

        return $resolved !== [] ? $resolved : $this->pages;
    }
}
