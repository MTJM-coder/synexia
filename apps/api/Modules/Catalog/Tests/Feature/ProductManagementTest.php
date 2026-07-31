<?php

namespace Modules\Catalog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Catalog\Models\Product;
use Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Models\SubscriptionPlan;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeManagerEmployee(Shop $shop): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create(['status' => 'active']);
        $managerRole = Role::where('slug', 'manager')->firstOrFail();

        ShopEmployee::factory()->create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'role_id' => $managerRole->id,
            'status' => ShopEmployee::STATUS_ACTIVE,
        ]);

        return $user;
    }

    public function test_employee_with_products_manage_can_create_a_product(): void
    {
        $plan = SubscriptionPlan::factory()->create(['max_products' => 10]);
        $shop = Shop::factory()->create(['subscription_plan_id' => $plan->id]);
        $manager = $this->makeManagerEmployee($shop);

        Sanctum::actingAs($manager);

        $response = $this->postJson("/api/v1/shops/{$shop->id}/products", [
            'name' => 'T-shirt basique',
            'base_price' => 5000,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('products', ['name' => 'T-shirt basique', 'shop_id' => $shop->id, 'status' => 'draft']);

        // has_variants = false : une variante par défaut doit exister.
        $product = Product::where('name', 'T-shirt basique')->firstOrFail();
        $this->assertSame(1, $product->variants()->count());
    }

    public function test_creation_is_blocked_when_plan_limit_is_reached(): void
    {
        $plan = SubscriptionPlan::factory()->create(['max_products' => 1]);
        $shop = Shop::factory()->create(['subscription_plan_id' => $plan->id]);
        $manager = $this->makeManagerEmployee($shop);
        Product::factory()->create(['shop_id' => $shop->id]);

        Sanctum::actingAs($manager);

        $response = $this->postJson("/api/v1/shops/{$shop->id}/products", [
            'name' => 'Produit en trop',
            'base_price' => 1000,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('products', ['name' => 'Produit en trop']);
    }

    public function test_employee_without_products_manage_cannot_create_a_product(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['status' => 'active']);
        $courierRole = Role::where('slug', 'courier')->firstOrFail();

        ShopEmployee::factory()->create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'role_id' => $courierRole->id,
            'status' => ShopEmployee::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/shops/{$shop->id}/products", ['name' => 'Intrus', 'base_price' => 1000])
            ->assertStatus(403);
    }

    public function test_public_index_only_shows_published_products(): void
    {
        $shop = Shop::factory()->create();
        Product::factory()->published()->create(['shop_id' => $shop->id, 'name' => 'Visible']);
        Product::factory()->create(['shop_id' => $shop->id, 'name' => 'Brouillon Caché', 'status' => Product::STATUS_DRAFT]);

        $response = $this->getJson("/api/v1/shops/{$shop->id}/products");

        $response->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Visible');
    }

    public function test_manager_sees_draft_products_too(): void
    {
        $shop = Shop::factory()->create();
        $manager = $this->makeManagerEmployee($shop);
        Product::factory()->create(['shop_id' => $shop->id, 'status' => Product::STATUS_DRAFT]);

        Sanctum::actingAs($manager);

        $this->getJson("/api/v1/shops/{$shop->id}/products")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
