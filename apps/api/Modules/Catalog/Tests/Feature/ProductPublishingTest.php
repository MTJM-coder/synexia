<?php

namespace Modules\Catalog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Brands\Models\Brand;
use Modules\Categories\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductImage;
use Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Tests\TestCase;

class ProductPublishingTest extends TestCase
{
    use RefreshDatabase;

    private function makeManager(Shop $shop): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['status' => 'active']);
        $role = Role::where('slug', 'manager')->firstOrFail();

        ShopEmployee::factory()->create([
            'shop_id' => $shop->id, 'user_id' => $user->id, 'role_id' => $role->id,
            'status' => ShopEmployee::STATUS_ACTIVE,
        ]);

        return $user;
    }

    private function completeProduct(Shop $shop): Product
    {
        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'category_id' => Category::factory()->create()->id,
            'brand_id' => Brand::factory()->create()->id,
            'description' => 'Une description complète.',
            'base_price' => 5000,
        ]);
        ProductImage::factory()->create(['product_id' => $product->id, 'is_primary' => true]);

        return $product;
    }

    public function test_cannot_publish_without_a_category(): void
    {
        $shop = Shop::factory()->create();
        $manager = $this->makeManager($shop);
        $product = $this->completeProduct($shop);
        $product->update(['category_id' => null]);

        Sanctum::actingAs($manager);

        $response = $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/publish");

        $response->assertStatus(422);
        $this->assertSame(Product::STATUS_DRAFT, $product->fresh()->status);
    }

    public function test_cannot_publish_without_an_image(): void
    {
        $shop = Shop::factory()->create();
        $manager = $this->makeManager($shop);
        $product = $this->completeProduct($shop);
        $product->images()->delete();

        Sanctum::actingAs($manager);

        $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/publish")
            ->assertStatus(422);
    }

    public function test_cannot_publish_a_no_variant_product_with_zero_price(): void
    {
        $shop = Shop::factory()->create();
        $manager = $this->makeManager($shop);
        $product = $this->completeProduct($shop);
        $product->update(['base_price' => 0]);

        Sanctum::actingAs($manager);

        $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/publish")
            ->assertStatus(422);
    }

    public function test_can_publish_a_fully_complete_product(): void
    {
        $shop = Shop::factory()->create();
        $manager = $this->makeManager($shop);
        $product = $this->completeProduct($shop);

        Sanctum::actingAs($manager);

        $response = $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/publish");

        $response->assertOk();
        $response->assertJsonPath('data.status', Product::STATUS_PUBLISHED);
        $this->assertNotNull($product->fresh()->published_at);
    }

    public function test_archived_product_can_be_archived_from_published(): void
    {
        $shop = Shop::factory()->create();
        $manager = $this->makeManager($shop);
        $product = $this->completeProduct($shop);
        $product->update(['status' => Product::STATUS_PUBLISHED]);

        Sanctum::actingAs($manager);

        $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', Product::STATUS_ARCHIVED);
    }
}
