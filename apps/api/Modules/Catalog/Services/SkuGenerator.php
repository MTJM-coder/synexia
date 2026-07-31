<?php

namespace Modules\Catalog\Services;

use Illuminate\Support\Str;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

/**
 * Règle retenue (validée) : SKU unique PAR BOUTIQUE, jamais globalement.
 *
 * Décision prise ici, à confirmer si elle ne correspond pas à votre intention :
 * un SKU de produit et un SKU de variante partagent le MÊME espace de noms
 * au sein d'une boutique — un scan code-barres en caisse ne sait pas à
 * l'avance s'il lit le SKU d'un produit simple ou d'une variante, donc les
 * deux ne doivent jamais entrer en collision l'un avec l'autre non plus.
 *
 * product_variants n'a pas de shop_id propre (seulement product_id) — la
 * vérification passe systématiquement par une jointure via products.shop_id.
 * Comme aucune contrainte d'unicité n'existe en base (voir migrations), tout
 * repose sur ce service — jamais un simple update SQL direct ailleurs.
 */
class SkuGenerator
{
    private const MAX_ATTEMPTS = 5;
    private const RANDOM_SUFFIX_LENGTH = 5;
    private const BASE_MAX_LENGTH = 12;

    public function generate(int $shopId, string $seed): string
    {
        $base = $this->buildBase($seed);

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $candidate = $attempt === 0
                ? $base
                : $base.'-'.Str::upper(Str::random(self::RANDOM_SUFFIX_LENGTH));

            if (! $this->isTaken($candidate, $shopId)) {
                return $candidate;
            }
        }

        // Dernier recours après MAX_ATTEMPTS collisions improbables :
        // suffixe plus long, entropie suffisante pour ne jamais boucler
        // indéfiniment plutôt que de risquer une boucle infinie.
        return $base.'-'.Str::upper(Str::random(8));
    }

    /**
     * @throws \DomainException si le SKU est déjà pris par un AUTRE produit/variante de la boutique
     */
    public function assertUnique(
        string $sku,
        int $shopId,
        ?int $exceptProductId = null,
        ?int $exceptVariantId = null,
    ): void {
        if ($this->isTaken($sku, $shopId, $exceptProductId, $exceptVariantId)) {
            throw new \DomainException("Le SKU « {$sku} » est déjà utilisé dans cette boutique.");
        }
    }

    private function isTaken(
        string $sku,
        int $shopId,
        ?int $exceptProductId = null,
        ?int $exceptVariantId = null,
    ): bool {
        $productQuery = Product::where('shop_id', $shopId)->where('sku', $sku);

        if ($exceptProductId !== null) {
            $productQuery->where('id', '!=', $exceptProductId);
        }

        if ($productQuery->exists()) {
            return true;
        }

        $variantQuery = ProductVariant::whereHas('product', fn ($q) => $q->where('shop_id', $shopId))
            ->where('sku', $sku);

        if ($exceptVariantId !== null) {
            $variantQuery->where('id', '!=', $exceptVariantId);
        }

        return $variantQuery->exists();
    }

    private function buildBase(string $seed): string
    {
        $slug = (string) Str::of($seed)->ascii()->upper()->replaceMatches('/[^A-Z0-9]+/', '');

        if ($slug === '') {
            $slug = 'PROD';
        }

        return Str::limit($slug, self::BASE_MAX_LENGTH, '');
    }
}
