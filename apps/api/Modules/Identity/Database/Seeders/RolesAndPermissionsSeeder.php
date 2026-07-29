<?php

namespace Modules\Identity\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Identity\Models\Permission;
use Modules\Identity\Models\Role;

/**
 * Rôles "système" (is_system = true), portée boutique ET plateforme.
 * À enrichir au fur et à mesure que Sales/Payments/Shipping/etc. sont construits.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /** @var array<string, string[]> module => [actions] */
    private const PERMISSIONS = [
        'employees' => ['view', 'manage'],
        'products' => ['view', 'manage'],
        'stock' => ['view', 'adjust', 'transfer'],
        'orders' => ['view', 'manage', 'cancel'],
        'customers' => ['view', 'manage'],
        'reports' => ['view'],
    ];

    /** @var array<string, string[]> */
    private const SYSTEM_ROLES = [
        'Owner' => ['*'],
        'Manager' => [
            'employees.view', 'employees.manage',
            'products.view', 'products.manage',
            'stock.view', 'stock.adjust', 'stock.transfer',
            'orders.view', 'orders.manage', 'orders.cancel',
            'customers.view', 'customers.manage',
            'reports.view',
        ],
        'Employee' => [
            'products.view',
            'stock.view',
            'orders.view', 'orders.manage',
            'customers.view',
        ],
        'Courier' => [
            'orders.view',
        ],
    ];

    /** @var array<string, string[]> */
    private const PLATFORM_PERMISSIONS = [
        'shops' => ['view', 'manage', 'suspend'],
        'subscriptions' => ['view', 'manage'],
        'commissions' => ['view', 'manage'],
        'platform-users' => ['view', 'manage'],
    ];

    /** @var array<string, string[]> */
    private const PLATFORM_ROLES = [
        'Super Admin' => ['*'],
        'Marketplace Admin' => [
            'platform.shops.view', 'platform.shops.manage', 'platform.shops.suspend',
            'platform.subscriptions.view', 'platform.subscriptions.manage',
            'platform.commissions.view',
            'platform.platform-users.view',
        ],
    ];

    public function run(): void
    {
        $shopPermissions = $this->seedPermissions(self::PERMISSIONS, prefix: null);
        $this->seedRoles(self::SYSTEM_ROLES, $shopPermissions, Role::GUARD_SHOP);

        $platformPermissions = $this->seedPermissions(self::PLATFORM_PERMISSIONS, prefix: 'platform');
        $this->seedRoles(self::PLATFORM_ROLES, $platformPermissions, Role::GUARD_PLATFORM);
    }

    /**
     * @param  array<string, string[]>  $definitions
     * @return array<string, Permission>
     */
    private function seedPermissions(array $definitions, ?string $prefix): array
    {
        $permissions = [];

        foreach ($definitions as $module => $actions) {
            $moduleName = $prefix ? "{$prefix}.{$module}" : $module;

            foreach ($actions as $action) {
                $name = "{$moduleName}.{$action}";

                $permissions[$name] = Permission::firstOrCreate(
                    ['name' => $name],
                    ['module' => $moduleName, 'description' => null],
                );
            }
        }

        return $permissions;
    }

    /**
     * @param  array<string, string[]>  $roleDefinitions
     * @param  array<string, Permission>  $permissions
     */
    private function seedRoles(array $roleDefinitions, array $permissions, string $guardScope): void
    {
        foreach ($roleDefinitions as $roleName => $grantedNames) {
            $role = Role::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($roleName), 'shop_id' => null],
                [
                    'name' => $roleName,
                    'guard_scope' => $guardScope,
                    'is_system' => true,
                ],
            );

            $toAttach = $grantedNames === ['*']
                ? array_values($permissions)
                : array_map(fn (string $name) => $permissions[$name], $grantedNames);

            $role->permissions()->syncWithoutDetaching(
                array_map(fn (Permission $p) => $p->id, $toAttach)
            );
        }
    }
}
