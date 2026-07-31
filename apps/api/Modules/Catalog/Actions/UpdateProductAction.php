<?php

namespace Modules\Catalog\Actions;

use Modules\Catalog\Models\Product;
use Modules\Catalog\Services\SkuGenerator;

class UpdateProductAction
{
    private const FILLABLE_KEYS = [
        'category_id', 'subcategory_id', 'brand_id', 'supplier_id',
        'name', 'description', 'short_description', 'sku', 'barcode',
        'has_variants', 'base_price', 'compare_at_price', 'cost_price',
        'tax_rate', 'weight_grams', 'length_cm', 'width_cm', 'height_cm',
        'is_featured',
    ];

    public function __construct(
        private readonly SkuGenerator $skuGenerator,
    ) {
    }

    /**
     * @throws \DomainException si has_variants change alors que le produit a déjà été utilisé,
     *                          ou si le nouveau SKU est déjà pris dans la boutique
     */
    public function execute(Product $product, array $data): Product
    {
        if (array_key_exists('has_variants', $data) && (bool) $data['has_variants'] !== $product->has_variants) {
            if ($product->hasBeenUsed()) {
                throw new \DomainException(
                    "Impossible de changer le type de produit (simple/variantes) : ".
                    "il existe déjà du stock, un mouvement d'inventaire ou une vente liée à ce produit."
                );
            }
        }

        if (array_key_exists('sku', $data) && $data['sku'] !== $product->sku && ! empty($data['sku'])) {
            $this->skuGenerator->assertUnique($data['sku'], $product->shop_id, exceptProductId: $product->id);
        }

        $product->fill(array_intersect_key($data, array_flip(self::FILLABLE_KEYS)));
        $product->save();

        return $product->fresh();
    }
}
