<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Catalog\Actions\GenerateVariantsAction;
use Modules\Catalog\Http\Requests\GenerateVariantsRequest;
use Modules\Catalog\Http\Requests\UpdateVariantRequest;
use Modules\Catalog\Http\Resources\ProductVariantResource;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Policies\ProductPolicy;
use Modules\Catalog\Services\SkuGenerator;
use Modules\Marketplace\Models\Shop;

class ProductVariantController extends Controller
{
    public function __construct(
        private readonly ProductPolicy $policy,
    ) {}

    // public function index(Request $request, Shop $shop, Product $product): JsonResponse
    // {
    //     abort_unless($product->shop_id === $shop->id, 404);

    //     $variants = $product->variants()->with(['attributeValues.attributeType'])->get();

    //     return ProductVariantResource::collection($variants)->response();
    // }
    public function index(Request $request, Shop $shop, Product $product): JsonResponse
    {
        abort_unless($product->shop_id === $shop->id, 404);

        if ($product->status !== Product::STATUS_PUBLISHED) {
            abort_unless(
                $request->user() !== null
                    && $this->policy->manage($request->user(), $shop->id),
                403
            );
        }

        $variants = $product->variants()
            ->with(['attributeValues.attributeType'])
            ->get();

        return ProductVariantResource::collection($variants)->response();
    }

    public function generate(
        GenerateVariantsRequest $request,
        Shop $shop,
        Product $product,
        GenerateVariantsAction $action,
    ): JsonResponse {
        abort_unless($product->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        try {
            $created = $action->execute($product, $request->validated('value_ids_by_type'));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // VariantGenerator peut renvoyer une Collection générique vide
        // (collect()) quand rien de nouveau n'a été créé — pas de ->load()
        // possible dessus. On recharge explicitement via une requête plutôt
        // que de supposer le type exact de la collection retournée.
        $variants = \Modules\Catalog\Models\ProductVariant::whereIn('id', $created->pluck('id'))
            ->with(['attributeValues.attributeType'])
            ->get();

        return ProductVariantResource::collection($variants)
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateVariantRequest $request,
        Shop $shop,
        Product $product,
        ProductVariant $variant,
        SkuGenerator $skuGenerator,
    ): JsonResponse {
        abort_unless($product->shop_id === $shop->id, 404);
        abort_unless($variant->product_id === $product->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $data = $request->validated();

        // Un SKU explicitement fourni doit rester unique dans la boutique —
        // même règle que celle appliquée à la création (voir SkuGenerator).
        if (array_key_exists('sku', $data) && $data['sku'] !== null && $data['sku'] !== $variant->sku) {
            try {
                $skuGenerator->assertUnique($data['sku'], $shop->id, exceptVariantId: $variant->id);
            } catch (\DomainException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        $variant->update($data);

        return (new ProductVariantResource($variant->fresh(['attributeValues.attributeType'])))->response();
    }
}
