<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Catalog\Http\Requests\StoreProductVideoRequest;
use Modules\Catalog\Http\Resources\ProductVideoResource;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVideo;
use Modules\Catalog\Policies\ProductPolicy;
use Modules\Marketplace\Models\Shop;

class ProductVideoController extends Controller
{
    public function __construct(
        private readonly ProductPolicy $policy,
    ) {
    }

    public function store(StoreProductVideoRequest $request, Shop $shop, Product $product): JsonResponse
    {
        abort_unless($product->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $sortOrder = $request->validated('sort_order')
            ?? (($product->videos()->max('sort_order') ?? -1) + 1);

        $video = ProductVideo::create([
            'product_id' => $product->id,
            'path' => $request->validated('path'),
            'thumbnail_path' => $request->validated('thumbnail_path'),
            'sort_order' => $sortOrder,
        ]);

        return (new ProductVideoResource($video))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Shop $shop, Product $product, ProductVideo $video): JsonResponse
    {
        abort_unless($product->shop_id === $shop->id, 404);
        abort_unless($video->product_id === $product->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $video->delete();

        return response()->json(['message' => 'Vidéo supprimée.']);
    }
}
