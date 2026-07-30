<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Models\SubscriptionPlan;
use Tests\TestCase;
use Modules\Identity\Contracts\ShopMembershipContract;
use Modules\Inventory\Contracts\WarehouseProvisioningContract;

class ShopManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_shop(): void
{
    // 1. Mock des contrats des modules Identity et Inventory
    $this->mock(WarehouseProvisioningContract::class, function ($mock) {
        $mock->shouldReceive('createDefaultWarehouse')->once();
    });

    $this->mock(ShopMembershipContract::class, function ($mock) {
        $mock->shouldReceive('createOwnerMembership')->once();
    });

    // 2. Préparation du jeu de données
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $plan = SubscriptionPlan::factory()->create();

    // 3. Exécution de la requête HTTP
    $response = $this->postJson('/api/v1/shops', [
        'name' => 'Prime Store',
        'subscription_plan_id' => $plan->id,
    ]);

    // 4. Assertions
    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Prime Store');

    $this->assertDatabaseHas('shops', [
        'name' => 'Prime Store',
        'owner_id' => $user->id,
        'subscription_plan_id' => $plan->id,
    ]);
}

    public function test_owner_can_view_their_shop(): void
    {
        $owner = User::factory()->create();

        Sanctum::actingAs($owner);

        $shop = Shop::factory()->create([
            'owner_id' => $owner->id,
        ]);

        $this->getJson("/api/v1/shops/{$shop->id}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $shop->uuid);
    }

    public function test_another_user_cannot_view_someone_elses_shop(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $shop = Shop::factory()->create([
            'owner_id' => $owner->id,
        ]);

        Sanctum::actingAs($intruder);

        $this->getJson("/api/v1/shops/{$shop->id}")
            ->assertForbidden();
    }

    public function test_owner_can_subscribe_to_a_new_plan(): void
    {
        $owner = User::factory()->create();

        Sanctum::actingAs($owner);

        $oldPlan = SubscriptionPlan::factory()->create();

        $newPlan = SubscriptionPlan::factory()->create();

        $shop = Shop::factory()->create([
            'owner_id' => $owner->id,
            'subscription_plan_id' => $oldPlan->id,
        ]);

        $this->patchJson("/api/v1/shops/{$shop->id}/subscription", [
            'subscription_plan_id' => $newPlan->id,
        ])
            ->assertOk();

        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'subscription_plan_id' => $newPlan->id,
        ]);
    }
}
