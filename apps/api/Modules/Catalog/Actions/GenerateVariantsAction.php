<?php

namespace Modules\Catalog\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Services\VariantGenerator;

class GenerateVariantsAction
{
    public function __construct(
        private readonly VariantGenerator $variantGenerator,
    ) {
    }

    /**
     * @param  array<int, int[]>  $valueIdsByType
     * @return Collection<int, \Modules\Catalog\Models\ProductVariant> les variantes nouvellement créées
     */
    public function execute(Product $product, array $valueIdsByType): Collection
    {
        if (! $product->has_variants) {
            throw new \DomainException(
                'Ce produit n\'est pas configuré pour avoir des variantes (has_variants = false). '.
                'Changez d\'abord ce réglage avant de générer des variantes.'
            );
        }

        return DB::transaction(fn () => $this->variantGenerator->generate($product, $valueIdsByType));
    }
}
