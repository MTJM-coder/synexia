<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Tests\TestCase;

class ShopStatusAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cannot_change_shop_status(): void
    {
        $owner = User::factory()->create();

        Sanctum::actingAs($owner);

        $shop = Shop::factory()->create([
            'owner_id' => $owner->id,
            'status' => Shop::STATUS_PENDING,
        ]);

        $this->patchJson("/api/v1/shops/{$shop->id}/status", [
            'status' => Shop::STATUS_ACTIVE,
        ])
            ->assertForbidden();
    }

    public function test_super_admin_can_activate_pending_shop(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Sanctum::actingAs($admin);

        $shop = Shop::factory()->create([
            'status' => Shop::STATUS_PENDING,
        ]);

        $this->patchJson("/api/v1/shops/{$shop->id}/status", [
            'status' => Shop::STATUS_ACTIVE,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', Shop::STATUS_ACTIVE);
    }

    public function test_super_admin_can_suspend_active_shop(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Sanctum::actingAs($admin);

        $shop = Shop::factory()->create([
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->patchJson("/api/v1/shops/{$shop->id}/status", [
            'status' => Shop::STATUS_SUSPENDED,
        ])
            ->assertOk();

        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'status' => Shop::STATUS_SUSPENDED,
        ]);
    }

    public function test_super_admin_can_close_active_shop(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Sanctum::actingAs($admin);

        $shop = Shop::factory()->create([
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->patchJson("/api/v1/shops/{$shop->id}/status", [
            'status' => Shop::STATUS_CLOSED,
        ])
            ->assertOk();

        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'status' => Shop::STATUS_CLOSED,
        ]);
    }

    public function test_closed_shop_cannot_be_reactivated(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Sanctum::actingAs($admin);

        $shop = Shop::factory()->create([
            'status' => Shop::STATUS_CLOSED,
        ]);

        $this->patchJson("/api/v1/shops/{$shop->id}/status", [
            'status' => Shop::STATUS_ACTIVE,
        ])
            ->assertStatus(422);
    }
}