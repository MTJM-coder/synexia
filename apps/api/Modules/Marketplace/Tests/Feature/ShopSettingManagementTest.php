<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Models\ShopSetting;
use Tests\TestCase;

class ShopSettingManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createShopWithSettings(int $ownerId): Shop
    {
        $shop = Shop::factory()->create(['owner_id' => $ownerId]);
        ShopSetting::factory()->create(['shop_id' => $shop->id]);

        return $shop->fresh(['settings']);
    }

    public function test_owner_can_view_their_shop_settings(): void
    {
        $owner = User::factory()->create();
        $shop = $this->createShopWithSettings($owner->id);

        Sanctum::actingAs($owner);

        $this->getJson("/api/v1/shops/{$shop->id}/settings")
            ->assertOk()
            ->assertJsonPath('data.currency', $shop->settings->currency);
    }

    public function test_owner_can_update_their_shop_settings(): void
    {
        $owner = User::factory()->create();
        $shop = $this->createShopWithSettings($owner->id);

        Sanctum::actingAs($owner);

        $response = $this->patchJson("/api/v1/shops/{$shop->id}/settings", [
            'currency' => 'USD',
            'tax_rate' => 5,
            'allow_delivery' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.currency', 'USD');
        $response->assertJsonPath('data.allow_delivery', false);

        $this->assertDatabaseHas('shop_settings', [
            'shop_id' => $shop->id,
            'currency' => 'USD',
            'allow_delivery' => false,
        ]);
    }

    public function test_non_owner_cannot_view_shop_settings(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $shop = $this->createShopWithSettings($owner->id);

        Sanctum::actingAs($intruder);

        $this->getJson("/api/v1/shops/{$shop->id}/settings")
            ->assertForbidden();
    }

    public function test_non_owner_cannot_update_shop_settings(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $shop = $this->createShopWithSettings($owner->id);

        Sanctum::actingAs($intruder);

        $this->patchJson("/api/v1/shops/{$shop->id}/settings", ['currency' => 'USD'])
            ->assertForbidden();
    }
}
