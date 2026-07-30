<?php

namespace Modules\Suppliers\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Modules\Suppliers\Models\Supplier;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_supplier_for_their_shop(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/shops/{$shop->id}/suppliers", [
            'name' => 'Fournisseur Textile SARL',
            'phone' => '+237600000000',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('suppliers', ['name' => 'Fournisseur Textile SARL', 'shop_id' => $shop->id]);
    }

    public function test_non_owner_cannot_create_a_supplier(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        Sanctum::actingAs($intruder);

        $this->postJson("/api/v1/shops/{$shop->id}/suppliers", ['name' => 'Intrusion'])
            ->assertStatus(403);
    }

    public function test_owner_can_list_only_their_shop_suppliers(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $otherShop = Shop::factory()->create();

        Supplier::factory()->count(2)->create(['shop_id' => $shop->id]);
        Supplier::factory()->create(['shop_id' => $otherShop->id]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/shops/{$shop->id}/suppliers");

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_cannot_access_a_supplier_from_a_different_shop_via_the_url(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::factory()->create(['owner_id' => $owner->id]);
        $otherShop = Shop::factory()->create(['owner_id' => $owner->id]); // même owner, boutique différente
        $foreignSupplier = Supplier::factory()->create(['shop_id' => $otherShop->id]);

        Sanctum::actingAs($owner);

        // Le fournisseur existe bien, appartient bien à l'utilisateur — mais
        // pas à CETTE boutique précise dans l'URL : doit être un 404, pas un 200.
        $this->getJson("/api/v1/shops/{$shop->id}/suppliers/{$foreignSupplier->id}")
            ->assertStatus(404);
    }
}
