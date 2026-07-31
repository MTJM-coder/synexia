<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Catalog\Http\Requests\StoreProductImageRequest;
use Modules\Catalog\Http\Resources\ProductImageResource;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductImage;
use Modules\Catalog\Policies\ProductPolicy;
use Modules\Catalog\Services\PrimaryMediaManager;
use Modules\Marketplace\Models\Shop;

class ProductImageController extends Controller
{
    public function __construct(
        private readonly ProductPolicy $policy,
    ) {
    }

    public function store(
        StoreProductImageRequest $request,
        Shop $shop,
        Product $product,
        PrimaryMediaManager $manager,
    ): JsonResponse {
        abort_unless($product->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $image = $manager->addImage(
            $product,
            $request->validated('path'),
            (bool) $request->validated('is_primary', false),
        );

        return (new ProductImageResource($image))->response()->setStatusCode(201);
    }

    public function setPrimary(
        Request $request,
        Shop $shop,
        Product $product,
        ProductImage $image,
        PrimaryMediaManager $manager,
    ): JsonResponse {
        abort_unless($product->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $manager->setPrimary($product, $image);

        return (new ProductImageResource($image->fresh()))->response();
    }

    public function destroy(
        Request $request,
        Shop $shop,
        Product $product,
        ProductImage $image,
        PrimaryMediaManager $manager,
    ): JsonResponse {
        abort_unless($product->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $manager->removeImage($product, $image);

        return response()->json(['message' => 'Image supprimée.']);
    }
}
