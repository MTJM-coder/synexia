<?php

namespace Modules\Catalog\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

/**
 * Génère les variantes d'un produit par produit cartésien des valeurs
 * d'attributs sélectionnées, groupées par type (jamais deux valeurs du même
 * type dans une même variante — ex: Couleur ne se combine jamais avec
 * Couleur, seulement avec Taille, Matière, etc.).
 *
 * Déterministe et idempotent : rappeler generate() avec le même jeu de
 * valeurs ne recrée rien (0 nouvelle variante). Ajouter une valeur (ex:
 * "Rouge" à Couleur) ne génère QUE les nouvelles combinaisons impliquant
 * Rouge — les combinaisons déjà existantes (Noir, Blanc × S, M) ne sont
 * jamais retouchées.
 */
class VariantGenerator
{
    public function __construct(
        private readonly SkuGenerator $skuGenerator,
    ) {
    }

    /**
     * @param array<int, int[]> $valueIdsByType chaque sous-tableau = les
     *        valeurs sélectionnées pour UN type d'attribut donné. L'ordre
     *        des sous-tableaux et des clés n'a pas d'importance.
     * @return Collection<int, ProductVariant> les variantes NOUVELLEMENT créées uniquement
     */
    public function generate(Product $product, array $valueIdsByType): Collection
    {
        $valueIdsByType = array_values(array_filter(
            $valueIdsByType,
            fn (array $ids) => count($ids) > 0,
        ));

        if (empty($valueIdsByType)) {
            return collect();
        }

        $combinations = $this->cartesianProduct($valueIdsByType);
        $existingSignatures = $this->existingSignatures($product);

        $created = collect();

        foreach ($combinations as $combination) {
            $signature = $this->signature($combination);

            if ($existingSignatures->contains($signature)) {
                continue; // idempotent : cette combinaison existe déjà
            }

            $created->push($this->createVariant($product, $combination));
            $existingSignatures->push($signature); // évite un doublon DANS ce même appel
        }

        $this->ensureExactlyOneDefault($product);

        return $created;
    }

    /**
     * @param int[] $combination
     */
    private function createVariant(Product $product, array $combination): ProductVariant
    {
        $seed = $product->name.'-'.implode('-', $combination);

        $variant = ProductVariant::create([
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'sku' => $this->skuGenerator->generate($product->shop_id, $seed),
            'price' => $product->base_price > 0 ? $product->base_price : 0,
            'is_default' => false,
            'is_active' => true,
        ]);

        $variant->attributeValues()->attach($combination);

        return $variant;
    }

    /**
     * @return Collection<int, string>
     */
    private function existingSignatures(Product $product): Collection
    {
        return $product->variants()
            ->with('attributeValues')
            ->get()
            ->map(fn (ProductVariant $variant) => $this->signature(
                $variant->attributeValues->pluck('id')->all(),
            ))
            // CRITIQUE : ->map() sur une Eloquent\Collection peut renvoyer une
            // Eloquent\Collection même quand le contenu n'est plus des
            // modèles. Sa méthode contains() est alors surchargée pour
            // comparer des modèles (appelle getKey() en interne) et plante
            // sur de simples chaînes ("Call to a member function getKey()
            // on string"). toBase() force une Collection standard, dont
            // contains() compare les valeurs normalement.
            ->toBase();
    }

    /**
     * @param int[] $valueIds
     */
    private function signature(array $valueIds): string
    {
        $sorted = $valueIds;
        sort($sorted);

        return implode('-', $sorted);
    }

    /**
     * @param array<int, int[]> $valueIdsByType
     * @return array<int, int[]> chaque élément = une combinaison (un id par type)
     */
    private function cartesianProduct(array $valueIdsByType): array
    {
        $result = [[]];

        foreach ($valueIdsByType as $valueIds) {
            $expanded = [];

            foreach ($result as $partial) {
                foreach ($valueIds as $valueId) {
                    $expanded[] = [...$partial, $valueId];
                }
            }

            $result = $expanded;
        }

        return $result;
    }

    /**
     * Garantit qu'il existe toujours exactement une variante par défaut dès
     * qu'au moins une variante existe — nécessaire pour que le reste du
     * système (panier, stock) référence toujours un product_variant_id
     * valide même si l'appelant ne précise rien.
     */
    private function ensureExactlyOneDefault(Product $product): void
    {
        $hasDefault = $product->variants()->where('is_default', true)->exists();

        if (! $hasDefault) {
            $product->variants()->orderBy('id')->first()?->update(['is_default' => true]);
        }
    }
}
