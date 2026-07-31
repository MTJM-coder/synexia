<?php

namespace Modules\Catalog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\AttributeType;
use Modules\Catalog\Models\AttributeValue;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Identity\Models\Permission;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Tests\TestCase;

class CatalogClosingFixesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ProductPolicy::manage() vérifie la permission "products.manage" via
     * PermissionResolverContract — PAS juste shop->owner_id === user->id.
     * Un Shop créé par factory n'a aucun ShopEmployee associé tant qu'on ne
     * passe pas par CreateShopAction ; ce helper reproduit la partie
     * nécessaire pour les tests (rôle + permission + ShopEmployee), comme
     * le fait déjà ProductManagementTest.
     */
    private function makeAuthorizedUser(Shop $shop): User
    {
        $user = User::factory()->create();

        $role = Role::factory()->create(['guard_scope' => Role::GUARD_SHOP]);
        $permission = Permission::factory()->create(['name' => 'products.manage']);
        $role->permissions()->attach($permission->id);

        ShopEmployee::factory()->create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);

        return $user;
    }

    public function test_cannot_delete_an_attribute_value_used_by_a_variant(): void
    {
        $shop = Shop::factory()->create();
        $user = $this->makeAuthorizedUser($shop);
        $type = AttributeType::factory()->create(['shop_id' => $shop->id]);
        $value = AttributeValue::factory()->create(['attribute_type_id' => $type->id]);
        $product = Product::factory()->withVariants()->create(['shop_id' => $shop->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $variant->attributeValues()->attach($value->id);

        $this->withHeader('Authorization', "Bearer {$user->createToken('t')->plainTextToken}")
            ->deleteJson("/api/v1/shops/{$shop->id}/attribute-types/{$type->id}/values/{$value->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('attribute_values', ['id' => $value->id]);
    }

    public function test_can_delete_an_unused_attribute_value(): void
    {
        $shop = Shop::factory()->create();
        $user = $this->makeAuthorizedUser($shop);
        $type = AttributeType::factory()->create(['shop_id' => $shop->id]);
        $value = AttributeValue::factory()->create(['attribute_type_id' => $type->id]);

        $this->withHeader('Authorization', "Bearer {$user->createToken('t')->plainTextToken}")
            ->deleteJson("/api/v1/shops/{$shop->id}/attribute-types/{$type->id}/values/{$value->id}")
            ->assertOk();
    }

    public function test_cannot_delete_an_attribute_type_with_a_value_in_use(): void
    {
        $shop = Shop::factory()->create();
        $user = $this->makeAuthorizedUser($shop);
        $type = AttributeType::factory()->create(['shop_id' => $shop->id]);
        $value = AttributeValue::factory()->create(['attribute_type_id' => $type->id]);
        $product = Product::factory()->withVariants()->create(['shop_id' => $shop->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $variant->attributeValues()->attach($value->id);

        $this->withHeader('Authorization', "Bearer {$user->createToken('t')->plainTextToken}")
            ->deleteJson("/api/v1/shops/{$shop->id}/attribute-types/{$type->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('attribute_types', ['id' => $type->id]);
    }

    public function test_can_update_an_attribute_type_name(): void
    {
        $shop = Shop::factory()->create();
        $user = $this->makeAuthorizedUser($shop);
        $type = AttributeType::factory()->create(['shop_id' => $shop->id, 'name' => 'Couleur']);

        $this->withHeader('Authorization', "Bearer {$user->createToken('t')->plainTextToken}")
            ->patchJson("/api/v1/shops/{$shop->id}/attribute-types/{$type->id}", ['name' => 'Couleurs'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Couleurs');
    }

    public function test_owner_can_add_and_remove_a_product_video(): void
    {
        $shop = Shop::factory()->create();
        $user = $this->makeAuthorizedUser($shop);
        $product = Product::factory()->create(['shop_id' => $shop->id]);

        $response = $this->withHeader('Authorization', "Bearer {$user->createToken('t')->plainTextToken}")
            ->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/videos", [
                'path' => 'videos/demo.mp4',
                'thumbnail_path' => 'videos/demo-thumb.jpg',
            ]);

        $response->assertCreated();
        $videoId = $response->json('data.id');

        $this->assertDatabaseHas('product_videos', ['id' => $videoId, 'product_id' => $product->id]);

        $this->withHeader('Authorization', "Bearer {$user->createToken('t2')->plainTextToken}")
            ->deleteJson("/api/v1/shops/{$shop->id}/products/{$product->id}/videos/{$videoId}")
            ->assertOk();

        $this->assertDatabaseMissing('product_videos', ['id' => $videoId]);
    }
}
