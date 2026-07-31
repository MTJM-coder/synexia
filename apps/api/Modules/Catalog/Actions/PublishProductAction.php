<?php

namespace Modules\Catalog\Actions;

use Modules\Catalog\Events\ProductPublished;
use Modules\Catalog\Models\Product;

class PublishProductAction
{
    /**
     * @throws \DomainException décrivant la première règle de complétude non respectée
     */
    public function execute(Product $product): Product
    {
        $this->assertCanBePublished($product);

        $product->forceFill([
            'status' => Product::STATUS_PUBLISHED,
            'published_at' => now(),
        ])->save();

        ProductPublished::dispatch($product);

        return $product->fresh();
    }

    /**
     * Règles validées : nom, description, catégorie, marque, au moins une
     * image, et un prix valide (base_price si simple, au moins une variante
     * active avec prix valide si has_variants).
     */
    private function assertCanBePublished(Product $product): void
    {
        if (blank($product->name)) {
            throw new \DomainException('Le nom du produit est requis pour publier.');
        }

        if (blank($product->description)) {
            throw new \DomainException('La description du produit est requise pour publier.');
        }

        if ($product->category_id === null) {
            throw new \DomainException('La catégorie est requise pour publier.');
        }

        if ($product->brand_id === null) {
            throw new \DomainException('La marque est requise pour publier.');
        }

        if ($product->images()->count() === 0) {
            throw new \DomainException('Au moins une image est requise pour publier.');
        }

        if ($product->has_variants) {
            $activeVariants = $product->variants()->where('is_active', true)->get();

            if ($activeVariants->isEmpty()) {
                throw new \DomainException('Au moins une variante active est requise pour publier.');
            }

            $hasInvalidPrice = $activeVariants->contains(
                fn ($variant) => $variant->price === null || $variant->price <= 0
            );

            if ($hasInvalidPrice) {
                throw new \DomainException('Chaque variante active doit avoir un prix valide (> 0) pour publier.');
            }
        } else {
            if ($product->base_price === null || $product->base_price <= 0) {
                throw new \DomainException('Le prix de base doit être supérieur à 0 pour publier.');
            }
        }
    }
}
