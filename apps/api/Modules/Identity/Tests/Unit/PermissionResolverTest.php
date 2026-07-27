<?php

namespace Modules\Identity\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Contracts\PermissionResolverContract;
use Modules\Identity\Models\Permission;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\ShopEmployeePermission;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Tests\TestCase;

/**
 * Couvre la règle métier centrale du module : permissions effectives =
 * (permissions du rôle) + (surcharges "grant") - (surcharges "deny"),
 * et un employé suspendu n'a jamais aucune permission.
 */
class PermissionResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_inherits_permissions_from_role(): void
    {
        [$employee, $permissions] = $this->makeEmployeeWithRolePermissions(['products.view', 'stock.view']);

        $resolved = $this->resolver()->resolveForEmployee($employee);

        $this->assertTrue($resolved->has('products.view'));
        $this->assertTrue($resolved->has('stock.view'));
        $this->assertFalse($resolved->has('orders.manage'));
    }

    public function test_granted_override_adds_a_permission_not_on_the_role(): void
    {
        [$employee, $permissions] = $this->makeEmployeeWithRolePermissions(['products.view']);
        $extra = Permission::factory()->create(['name' => 'orders.manage', 'module' => 'orders']);

        ShopEmployeePermission::create([
            'shop_employee_id' => $employee->id,
            'permission_id' => $extra->id,
            'is_granted' => true,
        ]);

        $resolved = $this->resolver()->resolveForEmployee($employee);

        $this->assertTrue($resolved->has('orders.manage'));
    }

    public function test_denied_override_removes_a_permission_granted_by_the_role(): void
    {
        [$employee, $permissions] = $this->makeEmployeeWithRolePermissions(['products.view', 'products.manage']);
        $toRevoke = $permissions['products.manage'];

        ShopEmployeePermission::create([
            'shop_employee_id' => $employee->id,
            'permission_id' => $toRevoke->id,
            'is_granted' => false,
        ]);

        $resolved = $this->resolver()->resolveForEmployee($employee);

        $this->assertTrue($resolved->has('products.view'));
        $this->assertFalse($resolved->has('products.manage'));
    }

    public function test_suspended_employee_has_no_permissions_regardless_of_role(): void
    {
        [$employee] = $this->makeEmployeeWithRolePermissions(['products.view', 'products.manage']);
        $employee->update(['status' => ShopEmployee::STATUS_SUSPENDED]);

        $resolved = $this->resolver()->resolveForEmployee($employee->fresh());

        $this->assertSame(0, $resolved->count());
    }

    public function test_forgetting_cache_forces_a_fresh_resolution(): void
    {
        [$employee, $permissions] = $this->makeEmployeeWithRolePermissions(['products.view']);

        $first = $this->resolver()->resolveForEmployee($employee);
        $this->assertFalse($first->has('orders.manage'));

        $extra = Permission::factory()->create(['name' => 'orders.manage', 'module' => 'orders']);
        ShopEmployeePermission::create([
            'shop_employee_id' => $employee->id,
            'permission_id' => $extra->id,
            'is_granted' => true,
        ]);

        // Sans invalidation, le cache renverrait encore l'ancien résultat.
        $this->resolver()->forgetForEmployee($employee);

        $second = $this->resolver()->resolveForEmployee($employee);
        $this->assertTrue($second->has('orders.manage'));
    }

    /**
     * @param  string[]  $permissionNames
     * @return array{0: ShopEmployee, 1: array<string, Permission>}
     */
    private function makeEmployeeWithRolePermissions(array $permissionNames): array
    {
        $shop = Shop::factory()->create();
        $role = Role::factory()->forShop($shop->id)->create();

        $permissions = [];
        foreach ($permissionNames as $name) {
            [$module] = explode('.', $name);
            $permissions[$name] = Permission::factory()->create(['name' => $name, 'module' => $module]);
        }
        $role->permissions()->attach(array_map(fn (Permission $p) => $p->id, $permissions));

        $employee = ShopEmployee::factory()->create([
            'shop_id' => $shop->id,
            'role_id' => $role->id,
            'status' => ShopEmployee::STATUS_ACTIVE,
        ]);

        return [$employee, $permissions];
    }

    private function resolver(): PermissionResolverContract
    {
        return $this->app->make(PermissionResolverContract::class);
    }
}
