<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Http\Resources\ProductResource;
use Modules\Catalog\Models\Product;

/**
 * Utilise l'index GIN déjà présent en base sur products(name, description)
 * (voir la migration corrigée pour Postgres). `plainto_tsquery` plutôt que
 * `to_tsquery` : accepte n'importe quelle saisie utilisateur brute sans
 * exiger une syntaxe d'opérateurs (&, |, !) — plus sûr et plus simple côté
 * client. Recherche volontairement marketplace-wide (pas scopée par
 * boutique) : c'est un moteur de recherche produit pour les clients.
 */
class ProductSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $term = $request->input('q');

        $products = Product::query()
            ->where('status', Product::STATUS_PUBLISHED)
            ->whereRaw(
                "to_tsvector('french', coalesce(name, '') || ' ' || coalesce(description, '')) @@ plainto_tsquery('french', ?)",
                [$term],
            )
            ->with(['images'])
            ->paginate(20);

        return ProductResource::collection($products)->response();
    }
}
