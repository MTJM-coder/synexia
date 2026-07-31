<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Catalog\Actions\ArchiveProductAction;
use Modules\Catalog\Actions\CreateProductAction;
use Modules\Catalog\Actions\PublishProductAction;
use Modules\Catalog\Actions\UpdateProductAction;
use Modules\Catalog\Http\Requests\StoreProductRequest;
use Modules\Catalog\Http\Requests\UpdateProductRequest;
use Modules\Catalog\Http\Resources\ProductResource;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Policies\ProductPolicy;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Exceptions\PlanLimitExceededException;


class ProductController extends Controller
{
    public function __construct(
        private readonly ProductPolicy $policy,
    ) {
    }

    /**
     * Public : seuls les produits "published" sont visibles. Un employé
     * avec la permission "products.view" (ou plus) voit tout, y compris
     * les brouillons — utile pour le back-office de la boutique.
     *
     * IMPORTANT : cette route n'a PAS le middleware auth:sanctum (elle doit
     * rester accessible sans connexion) — donc $request->user() serait
     * toujours null même avec un token valide. auth('sanctum')->user()
     * tente la résolution SANS exiger de succès (retourne null proprement
     * si pas de token, au lieu d'un 401).
     */
    public function index(Request $request, Shop $shop): JsonResponse
    {
        $user = auth('sanctum')->user();
        $canSeeAll = $user !== null && $this->policy->viewAny($user, $shop->id);

        $query = Product::with(['images'])->where('shop_id', $shop->id);

        if (! $canSeeAll) {
            $query->where('status', Product::STATUS_PUBLISHED);
        }

        return ProductResource::collection($query->paginate(20))->response();
    }

    public function store(StoreProductRequest $request, Shop $shop, CreateProductAction $action): JsonResponse
    {
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        try {
            $product = $action->execute($shop, $request->validated());
        } catch (PlanLimitExceededException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new ProductResource($product))->response()->setStatusCode(201);
    }
    public function show(Request $request, Shop $shop, Product $product): JsonResponse
    {
        abort_unless($product->shop_id === $shop->id, 404);

        $isPublished = $product->status === Product::STATUS_PUBLISHED;
        $user = auth('sanctum')->user();
        $canSeeAll = $user !== null && $this->policy->viewAny($user, $shop->id);

        abort_unless($isPublished || $canSeeAll, 403);

        return (new ProductResource($product->load(['variants.attributeValues.attributeType', 'images'])))->response();
    }

    public function update(UpdateProductRequest $request, Shop $shop, Product $product, UpdateProductAction $action): JsonResponse
    {
        abort_unless($product->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        try {
            $updated = $action->execute($product, $request->validated());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new ProductResource($updated))->response();
    }

    public function destroy(Request $request, Shop $shop, Product $product): JsonResponse
    {
        abort_unless($product->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $product->delete();

        return response()->json(['message' => 'Produit supprimé.']);
    }

    public function publish(Request $request, Shop $shop, Product $product, PublishProductAction $action): JsonResponse
    {
        abort_unless($product->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        try {
            $updated = $action->execute($product);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new ProductResource($updated))->response();
    }

    public function archive(Request $request, Shop $shop, Product $product, ArchiveProductAction $action): JsonResponse
    {
        abort_unless($product->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $updated = $action->execute($product);

        return (new ProductResource($updated))->response();
    }
}
