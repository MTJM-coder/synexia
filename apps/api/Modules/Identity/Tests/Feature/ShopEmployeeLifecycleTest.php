<?php

namespace Modules\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Actions\RemoveShopEmployeeAction;
use Modules\Identity\Actions\SuspendShopEmployeeAction;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Tests\TestCase;

class ShopEmployeeLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function ownerRole(): Role
    {
        return Role::factory()->create(['slug' => 'owner', 'guard_scope' => Role::GUARD_SHOP]);
    }

    public function test_cannot_suspend_the_last_active_owner(): void
    {
        $shop = Shop::factory()->create();
        $owner = ShopEmployee::factory()->create([
            'shop_id' => $shop->id,
            'role_id' => $this->ownerRole()->id,
        ]);
        $otherUser = User::factory()->create();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Impossible de suspendre le dernier Owner actif de la boutique.');

        app(SuspendShopEmployeeAction::class)->execute($owner, $otherUser->id);
    }

    public function test_can_suspend_a_regular_employee(): void
    {
        $shop = Shop::factory()->create();
        $role = Role::factory()->create(['slug' => 'employee']);
        $employee = ShopEmployee::factory()->create(['shop_id' => $shop->id, 'role_id' => $role->id]);
        $manager = User::factory()->create();

        $result = app(SuspendShopEmployeeAction::class)->execute($employee, $manager->id);

        $this->assertSame(ShopEmployee::STATUS_SUSPENDED, $result->status);
    }

    public function test_cannot_suspend_self(): void
    {
        $shop = Shop::factory()->create();
        $role = Role::factory()->create(['slug' => 'employee']);
        $user = User::factory()->create();
        $employee = ShopEmployee::factory()->create([
            'shop_id' => $shop->id,
            'role_id' => $role->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(\DomainException::class);

        app(SuspendShopEmployeeAction::class)->execute($employee, $user->id);
    }

    public function test_removing_an_employee_soft_deletes_it(): void
    {
        $shop = Shop::factory()->create();
        $role = Role::factory()->create(['slug' => 'employee']);
        $employee = ShopEmployee::factory()->create(['shop_id' => $shop->id, 'role_id' => $role->id]);
        $manager = User::factory()->create();

        app(RemoveShopEmployeeAction::class)->execute($employee, $manager->id);

        $this->assertSoftDeleted('shop_employees', ['id' => $employee->id]);
    }

    public function test_cannot_remove_the_last_owner(): void
    {
        $shop = Shop::factory()->create();
        $owner = ShopEmployee::factory()->create([
            'shop_id' => $shop->id,
            'role_id' => $this->ownerRole()->id,
        ]);
        $otherUser = User::factory()->create();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Impossible de retirer le dernier Owner de la boutique.');

        app(RemoveShopEmployeeAction::class)->execute($owner, $otherUser->id);
    }
}
