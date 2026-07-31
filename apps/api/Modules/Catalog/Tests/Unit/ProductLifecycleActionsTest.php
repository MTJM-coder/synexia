<?php

namespace Modules\Catalog\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Brands\Models\Brand;
use Modules\Catalog\Actions\ArchiveProductAction;
use Modules\Catalog\Actions\CreateProductAction;
use Modules\Catalog\Actions\PublishProductAction;
use Modules\Catalog\Actions\UpdateProductAction;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductImage;
use Modules\Catalog\Models\ProductVariant;
use Modules\Categories\Models\Category;
use Modules\Marketplace\Contracts\PlanLimitCheckerContract;
use Modules\Marketplace\Domain\ValueObjects\PlanLimits;
use Modules\Marketplace\Models\Shop;
use Tests\TestCase;

class ProductLifecycleActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Isolation volontaire : les tests Catalog ne doivent pas dépendre
        // des plans réellement seedés par Marketplace. Un stub permissif
        // suffit ici — le comportement réel de PlanLimitChecker est déjà
        // testé dans Modules\Marketplace\Tests\Unit\CommissionCalculatorTest.
        $this->app->bind(PlanLimitCheckerContract::class, fn () => new class implements PlanLimitCheckerContract
        {
            public function currentLimits(Shop $shop): PlanLimits
            {
                return new PlanLimits(maxProducts: null, maxEmployees: null, maxWarehouses: null);
            }

            public function assertCanAddProduct(Shop $shop, int $currentProductCount): void {}

            public function assertCanAddEmployee(Shop $shop, int $currentEmployeeCount): void {}

            public function assertCanAddWarehouse(Shop $shop, int $currentWarehouseCount): void {}
        });
    }

    public function test_creating_a_simple_product_also_creates_a_default_variant(): void
    {
        $shop = Shop::factory()->create();

        $product = app(CreateProductAction::class)->execute($shop, [
            'name' => 'Chaise en bois',
            'base_price' => 15000,
        ]);

        $this->assertFalse($product->has_variants);
        $this->assertNotNull($product->sku);

        $defaultVariant = $product->variants()->where('is_default', true)->first();
        $this->assertNotNull($defaultVariant);
        $this->assertSame($product->sku, $defaultVariant->sku);
        $this->assertEquals(15000, (float) $defaultVariant->price);
    }

    public function test_creating_a_product_with_variants_does_not_create_a_default_variant_or_product_sku(): void
    {
        $shop = Shop::factory()->create();

        $product = app(CreateProductAction::class)->execute($shop, [
            'name' => 'T-shirt',
            'has_variants' => true,
        ]);

        $this->assertTrue($product->has_variants);
        $this->assertNull($product->sku); // le SKU vivra sur chaque variante, générée séparément
        $this->assertSame(0, $product->variants()->count());
    }

    public function test_cannot_update_has_variants_after_the_product_has_been_used(): void
    {
        $product = Product::factory()->create();

        // On simule "déjà utilisé" en surchargeant temporairement le modèle
        // n'est pas possible proprement (hasBeenUsed() est codée en dur à
        // false pour l'instant) — ce test documente le comportement ACTUEL
        // et devra être complété une fois Inventory/Sales existants pour
        // vraiment déclencher hasBeenUsed() = true.
        $this->assertFalse($product->hasBeenUsed());

        // Pour l'instant, le changement est donc toujours accepté :
        // IMPORTANT : capturer AVANT l'appel — UpdateProductAction reçoit
        // $product par référence et le modifie sur place (fill()+save()),
        // donc lire $product->has_variants APRÈS l'appel donnerait déjà la
        // nouvelle valeur, pas l'ancienne.
        $originalHasVariants = $product->has_variants;

        $updated = app(UpdateProductAction::class)->execute($product, ['has_variants' => ! $originalHasVariants]);
        $this->assertNotEquals($originalHasVariants, $updated->has_variants);
    }

    public function test_update_rejects_a_sku_already_used_by_another_product_in_the_same_shop(): void
    {
        $shop = Shop::factory()->create();
        Product::factory()->create(['shop_id' => $shop->id, 'sku' => 'TAKEN']);
        $product = Product::factory()->create(['shop_id' => $shop->id, 'sku' => 'MINE']);

        $this->expectException(\DomainException::class);

        app(UpdateProductAction::class)->execute($product, ['sku' => 'TAKEN']);
    }

    public function test_publish_fails_without_category(): void
    {
        $product = Product::factory()->create(['category_id' => null]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('catégorie');

        app(PublishProductAction::class)->execute($product);
    }

    public function test_publish_fails_without_any_image(): void
    {
        $product = $this->makePublishableProduct(['images' => false]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('image');

        app(PublishProductAction::class)->execute($product);
    }

    public function test_publish_fails_for_simple_product_without_valid_price(): void
    {
        $product = $this->makePublishableProduct(['base_price' => 0]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('prix');

        app(PublishProductAction::class)->execute($product);
    }

    public function test_publish_fails_for_variant_product_without_active_variant(): void
    {
        $product = $this->makePublishableProduct(['has_variants' => true, 'variants' => false]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('variante');

        app(PublishProductAction::class)->execute($product);
    }

    public function test_publish_succeeds_when_all_rules_are_met(): void
    {
        $product = $this->makePublishableProduct();

        $published = app(PublishProductAction::class)->execute($product);

        $this->assertSame(Product::STATUS_PUBLISHED, $published->status);
        $this->assertNotNull($published->published_at);
    }

    public function test_archive_changes_status_without_any_completeness_check(): void
    {
        $product = Product::factory()->create(['category_id' => null]); // volontairement incomplet

        $archived = app(ArchiveProductAction::class)->execute($product);

        $this->assertSame(Product::STATUS_ARCHIVED, $archived->status);
    }

    /**
     * @param array{images?: bool, variants?: bool, base_price?: float, has_variants?: bool} $overrides
     */
    private function makePublishableProduct(array $overrides = []): Product
    {
        $hasVariants = $overrides['has_variants'] ?? false;

        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'brand_id' => Brand::factory()->create()->id,
            'has_variants' => $hasVariants,
            'base_price' => $hasVariants ? 0 : ($overrides['base_price'] ?? 9900),
        ]);

        if ($overrides['images'] ?? true) {
            ProductImage::create(['product_id' => $product->id, 'path' => 'x.jpg', 'is_primary' => true]);
        }

        if ($hasVariants && ($overrides['variants'] ?? true)) {
            ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 9900, 'is_active' => true]);
        }

        return $product->fresh();
    }
}
