<?php

namespace Modules\Catalog\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Catalog\Events\ProductCreated;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Services\SkuGenerator;
use Modules\Marketplace\Contracts\PlanLimitCheckerContract;
use Modules\Marketplace\Models\Shop;

class CreateProductAction
{
    public function __construct(
        private readonly PlanLimitCheckerContract $planLimitChecker,
        private readonly SkuGenerator $skuGenerator,
    ) {
    }

    /**
     * @param array{
     *     category_id?: ?int, subcategory_id?: ?int, brand_id?: ?int, supplier_id?: ?int,
     *     name: string, description?: ?string, short_description?: ?string,
     *     sku?: ?string, barcode?: ?string, has_variants?: bool, base_price?: float,
     *     compare_at_price?: ?float, cost_price?: ?float, tax_rate?: ?float,
     *     weight_grams?: ?int, length_cm?: ?float, width_cm?: ?float, height_cm?: ?float,
     * } $data
     */
    public function execute(Shop $shop, array $data): Product
    {
        // Vérifié AVANT toute écriture, comme demandé.
        $currentProductCount = Product::where('shop_id', $shop->id)->count();
        $this->planLimitChecker->assertCanAddProduct($shop, $currentProductCount);

        $hasVariants = $data['has_variants'] ?? false;

        return DB::transaction(function () use ($shop, $data, $hasVariants) {
            $sku = $this->resolveProductSku($shop->id, $data);

            $product = Product::create([
                'uuid' => (string) Str::uuid(),
                'shop_id' => $shop->id,
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'name' => $data['name'],
                'slug' => Str::slug($data['name']).'-'.Str::random(6),
                'description' => $data['description'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'sku' => $hasVariants ? null : $sku, // le SKU vit sur la variante par défaut si has_variants=false, voir plus bas
                'barcode' => $data['barcode'] ?? null,
                'has_variants' => $hasVariants,
                'base_price' => $hasVariants ? 0 : ($data['base_price'] ?? 0),
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'cost_price' => $data['cost_price'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? null,
                'weight_grams' => $data['weight_grams'] ?? null,
                'length_cm' => $data['length_cm'] ?? null,
                'width_cm' => $data['width_cm'] ?? null,
                'height_cm' => $data['height_cm'] ?? null,
                'status' => Product::STATUS_DRAFT,
            ]);

            if (! $hasVariants) {
                // Décision du roadmap : même sans variantes, une ligne
                // product_variants "par défaut" existe toujours, pour que
                // Stock/Sales référencent systématiquement un
                // product_variant_id, sans branche conditionnelle ailleurs.
                // Son SKU reprend celui du produit — ce n'est PAS une
                // collision (c'est la même entité), donc pas de nouvel
                // appel à assertUnique()/generate() ici.
                ProductVariant::create([
                    'uuid' => (string) Str::uuid(),
                    'product_id' => $product->id,
                    'sku' => $sku,
                    'price' => $product->base_price,
                    'is_default' => true,
                    'is_active' => true,
                ]);
            }

            ProductCreated::dispatch($product);

            return $product->fresh();
        });
    }

    private function resolveProductSku(int $shopId, array $data): string
    {
        if (! empty($data['sku'])) {
            $this->skuGenerator->assertUnique($data['sku'], $shopId);

            return $data['sku'];
        }

        return $this->skuGenerator->generate($shopId, $data['name']);
    }
}
