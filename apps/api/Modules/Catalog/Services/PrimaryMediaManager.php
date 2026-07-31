<?php

namespace Modules\Catalog\Services;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductImage;

/**
 * Règle du roadmap (décision 6) : une seule image principale à la fois,
 * gérée ici — jamais par un update SQL direct dans un Controller.
 */
class PrimaryMediaManager
{
    public function addImage(Product $product, string $path, bool $asPrimary = false): ProductImage
    {
        return DB::transaction(function () use ($product, $path, $asPrimary) {
            $isFirstImage = $product->images()->count() === 0;
            $shouldBePrimary = $asPrimary || $isFirstImage; // la toute première image devient primaire par défaut

            if ($shouldBePrimary) {
                $this->clearExistingPrimary($product);
            }

            return ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'sort_order' => $product->images()->count(),
                'is_primary' => $shouldBePrimary,
            ]);
        });
    }

    public function setPrimary(Product $product, ProductImage $image): void
    {
        if ($image->product_id !== $product->id) {
            throw new \InvalidArgumentException('Cette image n\'appartient pas à ce produit.');
        }

        DB::transaction(function () use ($product, $image) {
            $this->clearExistingPrimary($product);
            $image->update(['is_primary' => true]);
        });
    }

    public function removeImage(Product $product, ProductImage $image): void
    {
        if ($image->product_id !== $product->id) {
            throw new \InvalidArgumentException('Cette image n\'appartient pas à ce produit.');
        }

        DB::transaction(function () use ($product, $image) {
            $wasPrimary = $image->is_primary;
            $image->delete();

            if ($wasPrimary) {
                // Une image supprimée qui était primaire : la suivante par
                // ordre d'affichage prend le relais, s'il en reste une.
                $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
            }
        });
    }

    private function clearExistingPrimary(Product $product): void
    {
        $product->images()->where('is_primary', true)->update(['is_primary' => false]);
    }
}
