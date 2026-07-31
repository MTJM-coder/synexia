<?php

namespace Modules\Catalog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Catalog\Models\AttributeType;
use Modules\Catalog\Models\AttributeValue;
use Modules\Catalog\Models\Product;
use Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Tests\TestCase;

class VariantGenerationTest extends TestCase
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

    public function test_generates_the_cartesian_product_of_attribute_values(): void
    {
        $shop = Shop::factory()->create();
        $manager = $this->makeManager($shop);
        $product = Product::factory()->withVariants()->create(['shop_id' => $shop->id]);

        $color = AttributeType::factory()->create(['shop_id' => $shop->id, 'name' => 'Couleur']);
        $black = AttributeValue::factory()->create(['attribute_type_id' => $color->id, 'value' => 'Noir']);
        $white = AttributeValue::factory()->create(['attribute_type_id' => $color->id, 'value' => 'Blanc']);

        $size = AttributeType::factory()->create(['shop_id' => $shop->id, 'name' => 'Taille']);
        $s = AttributeValue::factory()->create(['attribute_type_id' => $size->id, 'value' => 'S']);
        $m = AttributeValue::factory()->create(['attribute_type_id' => $size->id, 'value' => 'M']);

        Sanctum::actingAs($manager);

        $response = $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/variants/generate", [
            'value_ids_by_type' => [
                [$black->id, $white->id],
                [$s->id, $m->id],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonCount(4, 'data'); // 2 couleurs x 2 tailles = 4 combinaisons

        $this->assertSame(4, $product->variants()->count());
    }

    public function test_regenerating_does_not_duplicate_existing_combinations(): void
    {
        $shop = Shop::factory()->create();
        $manager = $this->makeManager($shop);
        $product = Product::factory()->withVariants()->create(['shop_id' => $shop->id]);

        $color = AttributeType::factory()->create(['shop_id' => $shop->id]);
        $black = AttributeValue::factory()->create(['attribute_type_id' => $color->id, 'value' => 'Noir']);
        $red = AttributeValue::factory()->create(['attribute_type_id' => $color->id, 'value' => 'Rouge']);

        Sanctum::actingAs($manager);

        // Première génération : juste Noir.
        $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/variants/generate", [
            'value_ids_by_type' => [[$black->id]],
        ])->assertCreated();

        $this->assertSame(1, $product->variants()->count());

        // Deuxième appel : Noir + Rouge — Noir existe déjà, seul Rouge doit être créé.
        $response = $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/variants/generate", [
            'value_ids_by_type' => [[$black->id, $red->id]],
        ]);

        $response->assertCreated();
        $response->assertJsonCount(1, 'data'); // une seule NOUVELLE variante retournée

        $this->assertSame(2, $product->variants()->count()); // pas 3, pas de doublon
    }

    public function test_generation_is_rejected_for_a_product_without_has_variants(): void
    {
        $shop = Shop::factory()->create();
        $manager = $this->makeManager($shop);
        $product = Product::factory()->create(['shop_id' => $shop->id, 'has_variants' => false]);

        $color = AttributeType::factory()->create(['shop_id' => $shop->id]);
        $value = AttributeValue::factory()->create(['attribute_type_id' => $color->id]);

        Sanctum::actingAs($manager);

        $this->postJson("/api/v1/shops/{$shop->id}/products/{$product->id}/variants/generate", [
            'value_ids_by_type' => [[$value->id]],
        ])->assertStatus(422);
    }
}
