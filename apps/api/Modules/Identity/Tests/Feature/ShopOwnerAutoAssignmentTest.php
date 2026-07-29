<?php

namespace Modules\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Contracts\PermissionResolverContract;
use Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\User;
use Modules\Marketplace\Actions\CreateShopAction;
use Modules\Marketplace\Models\SubscriptionPlan;
use Tests\TestCase;

class ShopOwnerAutoAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_shop_automatically_creates_an_owner_employee(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        // Résolu depuis le conteneur (pas de "new") pour que Laravel injecte
        // ShopMembershipContract et WarehouseProvisioningContract automatiquement.
        $shop = $this->app->make(CreateShopAction::class)->execute(
            owner: $owner,
            shopData: ['name' => 'Ma Super Boutique'],
            plan: $plan,
        );

        $employee = ShopEmployee::where('shop_id', $shop->id)
            ->where('user_id', $owner->id)
            ->first();

        $this->assertNotNull($employee, 'Aucun ShopEmployee créé pour le propriétaire.');
        $this->assertSame('active', $employee->status);
        $this->assertSame('Owner', $employee->role->name);
        $this->assertSame('Propriétaire', $employee->job_title);
    }

    public function test_owner_employee_has_all_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $shop = $this->app->make(CreateShopAction::class)->execute(
            owner: $owner,
            shopData: ['name' => 'Autre Boutique'],
            plan: $plan,
        );

        $employee = ShopEmployee::where('shop_id', $shop->id)->firstOrFail();
        $resolver = $this->app->make(PermissionResolverContract::class);

        $permissions = $resolver->resolveForEmployee($employee);

        $this->assertGreaterThan(0, $permissions->count());
        $this->assertTrue($permissions->has('employees.manage'));
    }
}