<?php

namespace Modules\Identity\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Contracts\PermissionResolverContract;
use Modules\Identity\Models\Permission;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\ShopEmployeePermission;
use Tests\TestCase;

class PermissionResolverTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployeeWithRolePermissions(array $permissionNames): ShopEmployee
    {
        $role = Role::factory()->create();

        foreach ($permissionNames as $name) {
            $permission = Permission::factory()->create(['name' => $name]);
            $role->permissions()->attach($permission->id);
        }

        return ShopEmployee::factory()->create(['role_id' => $role->id]);
    }

    public function test_employee_inherits_permissions_from_role(): void
    {
        $employee = $this->makeEmployeeWithRolePermissions(['products.view', 'stock.view']);

        $resolved = app(PermissionResolverContract::class)->resolveForEmployee($employee);

        $this->assertTrue($resolved->has('products.view'));
        $this->assertTrue($resolved->has('stock.view'));
        $this->assertFalse($resolved->has('orders.manage'));
    }

    public function test_granted_override_adds_a_permission_not_on_the_role(): void
    {
        $employee = $this->makeEmployeeWithRolePermissions(['products.view']);
        $extra = Permission::factory()->create(['name' => 'orders.manage']);

        ShopEmployeePermission::factory()->create([
            'shop_employee_id' => $employee->id,
            'permission_id' => $extra->id,
            'is_granted' => true,
        ]);

        $resolved = app(PermissionResolverContract::class)->resolveForEmployee($employee);

        $this->assertTrue($resolved->has('orders.manage'));
    }

    public function test_denied_override_removes_a_permission_granted_by_the_role(): void
    {
        $employee = $this->makeEmployeeWithRolePermissions(['products.view', 'stock.adjust']);
        $denied = Permission::where('name', 'stock.adjust')->first();

        ShopEmployeePermission::factory()->denied()->create([
            'shop_employee_id' => $employee->id,
            'permission_id' => $denied->id,
        ]);

        $resolved = app(PermissionResolverContract::class)->resolveForEmployee($employee);

        $this->assertFalse($resolved->has('stock.adjust'));
        $this->assertTrue($resolved->has('products.view'));
    }

    public function test_suspended_employee_has_no_permissions_regardless_of_role(): void
    {
        $employee = $this->makeEmployeeWithRolePermissions(['products.view']);
        $employee->update(['status' => ShopEmployee::STATUS_SUSPENDED]);

        $resolved = app(PermissionResolverContract::class)->resolveForEmployee($employee->fresh());

        $this->assertSame(0, $resolved->count());
    }

    public function test_forgetting_cache_forces_a_fresh_resolution(): void
    {
        $employee = $this->makeEmployeeWithRolePermissions(['products.view']);
        $resolver = app(PermissionResolverContract::class);

        $resolver->resolveForEmployee($employee); // met en cache

        $newPermission = Permission::factory()->create(['name' => 'stock.view']);
        $employee->role->permissions()->attach($newPermission->id);

        $resolver->forgetForEmployee($employee);
        $resolved = $resolver->resolveForEmployee($employee->fresh());

        $this->assertTrue($resolved->has('stock.view'));
    }
}
