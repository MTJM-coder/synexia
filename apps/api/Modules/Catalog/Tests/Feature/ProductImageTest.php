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
use Tests\TestCase;

class ProductImageTest extends TestCase
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

    public function test_first_image_becomes_primary_automatically(): void
    {
        $shop = Shop::factory()->create();
        $manager = $this->makeManager($shop);
        $product = Product::factory()->create(['shop_id' => $shop->id]);

        Sanctum::actingAs($manager);

        $response = $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/images", [
            'path' => 'products/premiere-image.jpg',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.is_primary', true);
    }

    public function test_setting_a_new_primary_image_unsets_the_previous_one(): void
    {
        $shop = Shop::factory()->create();
        $manager = $this->makeManager($shop);
        $product = Product::factory()->create(['shop_id' => $shop->id]);
        Sanctum::actingAs($manager);

        $first = $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/images", [
            'path' => 'products/premiere.jpg',
        ])->json('data');

        $second = $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/images", [
            'path' => 'products/deuxieme.jpg',
        ])->json('data');

        $this->assertTrue($first['is_primary']);
        $this->assertFalse($second['is_primary']);

        $this->patchJson("/api/v1/shops/{$shop->id}/products/{$product->id}/images/{$second['id']}/primary")
            ->assertOk()
            ->assertJsonPath('data.is_primary', true);

        $this->assertDatabaseHas('product_images', ['id' => $first['id'], 'is_primary' => false]);
        $this->assertDatabaseHas('product_images', ['id' => $second['id'], 'is_primary' => true]);
    }

    public function test_removing_the_primary_image_promotes_the_next_one(): void
    {
        $shop = Shop::factory()->create();
        $manager = $this->makeManager($shop);
        $product = Product::factory()->create(['shop_id' => $shop->id]);
        Sanctum::actingAs($manager);

        $first = $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/images", [
            'path' => 'products/premiere.jpg',
        ])->json('data');

        $second = $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/images", [
            'path' => 'products/deuxieme.jpg',
        ])->json('data');

        $this->deleteJson("/api/v1/shops/{$shop->id}/products/{$product->id}/images/{$first['id']}")
            ->assertOk();

        $this->assertDatabaseHas('product_images', ['id' => $second['id'], 'is_primary' => true]);
    }
}
