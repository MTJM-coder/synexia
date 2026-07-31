<?php

namespace Modules\Catalog\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Services\SkuGenerator;
use Modules\Marketplace\Models\Shop;
use Tests\TestCase;

class SkuGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_a_base_sku_from_a_product_name(): void
    {
        $shop = Shop::factory()->create();

        $sku = app(SkuGenerator::class)->generate($shop->id, 'T-shirt Premium');

        $this->assertSame('TSHIRTPREMIU', $sku); // ASCII, majuscules, sans espaces/tirets, tronqué à 12
    }

    public function test_falls_back_to_a_default_base_when_seed_has_no_alphanumeric_characters(): void
    {
        $shop = Shop::factory()->create();

        $sku = app(SkuGenerator::class)->generate($shop->id, '!!!');

        $this->assertStringStartsWith('PROD', $sku);
    }

    public function test_appends_a_random_suffix_on_collision_with_an_existing_product_sku(): void
    {
        $shop = Shop::factory()->create();
        Product::factory()->create(['shop_id' => $shop->id, 'sku' => 'CHAISE']);

        $sku = app(SkuGenerator::class)->generate($shop->id, 'Chaise');

        $this->assertNotSame('CHAISE', $sku);
        $this->assertStringStartsWith('CHAISE-', $sku);
    }

    public function test_a_product_sku_and_a_variant_sku_share_the_same_namespace(): void
    {
        $shop = Shop::factory()->create();
        $product = Product::factory()->create(['shop_id' => $shop->id]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'ABC123']);

        $this->expectException(\DomainException::class);

        app(SkuGenerator::class)->assertUnique('ABC123', $shop->id);
    }

    public function test_same_sku_is_allowed_across_different_shops(): void
    {
        $shopA = Shop::factory()->create();
        $shopB = Shop::factory()->create();
        Product::factory()->create(['shop_id' => $shopA->id, 'sku' => 'SAME-SKU']);

        // Ne doit lever aucune exception : boutiques différentes.
        app(SkuGenerator::class)->assertUnique('SAME-SKU', $shopB->id);
        $this->assertTrue(true);
    }

    public function test_assert_unique_ignores_the_product_itself_when_updating(): void
    {
        $shop = Shop::factory()->create();
        $product = Product::factory()->create(['shop_id' => $shop->id, 'sku' => 'KEEP-ME']);

        // Ne doit pas lever d'exception : c'est LUI-MÊME qui porte déjà ce SKU.
        app(SkuGenerator::class)->assertUnique('KEEP-ME', $shop->id, exceptProductId: $product->id);
        $this->assertTrue(true);
    }

    public function test_assert_unique_still_throws_for_a_different_product_with_except_id_set(): void
    {
        $shop = Shop::factory()->create();
        $productA = Product::factory()->create(['shop_id' => $shop->id, 'sku' => 'TAKEN']);
        $productB = Product::factory()->create(['shop_id' => $shop->id]);

        $this->expectException(\DomainException::class);

        app(SkuGenerator::class)->assertUnique('TAKEN', $shop->id, exceptProductId: $productB->id);
    }
}
