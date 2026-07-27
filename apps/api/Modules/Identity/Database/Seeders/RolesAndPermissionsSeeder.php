<?php

namespace Modules\Identity\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Identity\Models\Permission;
use Modules\Identity\Models\Role;

/**
 * Rôles "système" (is_system = true) disponibles pour toutes les boutiques,
 * et la liste de permissions de base couvrant les modules déjà scaffoldés.
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

    /** @var array<string, string[]> nom du rôle système => permissions accordées ('*' = toutes) */
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

    public function run(): void
    {
        $permissions = $this->seedPermissions();
        $this->seedSystemRoles($permissions);
    }

    /**
     * @return array<string, Permission> nom de permission => modèle
     */
    private function seedPermissions(): array
    {
        $permissions = [];

        foreach (self::PERMISSIONS as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";

                $permissions[$name] = Permission::firstOrCreate(
                    ['name' => $name],
                    ['module' => $module, 'description' => null],
                );
            }
        }

        return $permissions;
    }

    /**
     * @param  array<string, Permission>  $permissions
     */
    private function seedSystemRoles(array $permissions): void
    {
        foreach (self::SYSTEM_ROLES as $roleName => $grantedNames) {
            $role = Role::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($roleName), 'shop_id' => null],
                [
                    'name' => $roleName,
                    'guard_scope' => Role::GUARD_SHOP,
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
