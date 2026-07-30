<?php

namespace Modules\Brands\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Brands\Http\Requests\StoreBrandRequest;
use Modules\Brands\Http\Requests\UpdateBrandRequest;
use Modules\Brands\Http\Resources\BrandResource;
use Modules\Brands\Models\Brand;
use Modules\Marketplace\Models\Shop;

class BrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Brand::query();

        if ($request->filled('shop_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('shop_id', $request->input('shop_id'))->orWhereNull('shop_id');
            });
        } else {
            $query->whereNull('shop_id');
        }

        return BrandResource::collection($query->orderBy('name')->get())->response();
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $shopId = $request->validated('shop_id');
        $this->authorizeWrite($request, $shopId);

        $brand = Brand::create([
            ...$request->validated(),
            'slug' => Str::slug($request->validated('name')).'-'.Str::random(6),
        ]);

        return (new BrandResource($brand))->response()->setStatusCode(201);
    }

    public function show(Brand $brand): JsonResponse
    {
        return (new BrandResource($brand))->response();
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $this->authorizeWrite($request, $brand->shop_id);

        $brand->update($request->validated());

        return (new BrandResource($brand->fresh()))->response();
    }

    public function destroy(Request $request, Brand $brand): JsonResponse
    {
        $this->authorizeWrite($request, $brand->shop_id);

        $brand->delete();

        return response()->json(['message' => 'Marque supprimée.']);
    }

    private function authorizeWrite(Request $request, ?int $shopId): void
    {
        $user = $request->user();
        abort_if($user === null, 401);

        if ($shopId === null) {
            abort_unless($user->is_super_admin, 403, 'Seul un Super Admin peut gérer une marque globale.');

            return;
        }

        $isOwner = Shop::where('id', $shopId)->where('owner_id', $user->id)->exists();
        abort_unless($user->is_super_admin || $isOwner, 403);
    }
}
