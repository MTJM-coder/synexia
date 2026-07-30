<?php

namespace Modules\Suppliers\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Models\Shop;
use Modules\Suppliers\Http\Requests\StoreSupplierRequest;
use Modules\Suppliers\Http\Requests\UpdateSupplierRequest;
use Modules\Suppliers\Http\Resources\SupplierResource;
use Modules\Suppliers\Models\Supplier;

/**
 * Contrairement à Categories/Brands, un fournisseur n'a pas de vocation
 * publique (coordonnées internes, conditions de paiement...) — donc pas
 * d'index/show public ici, tout est scopé par boutique et authentifié.
 */
class SupplierController extends Controller
{
    public function index(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request, $shop);

        $suppliers = Supplier::where('shop_id', $shop->id)->orderBy('name')->get();

        return SupplierResource::collection($suppliers)->response();
    }

    public function store(StoreSupplierRequest $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request, $shop);

        $supplier = Supplier::create([...$request->validated(), 'shop_id' => $shop->id]);

        return (new SupplierResource($supplier))->response()->setStatusCode(201);
    }

    public function show(Request $request, Shop $shop, Supplier $supplier): JsonResponse
    {
        $this->authorizeShop($request, $shop);
        abort_unless($supplier->shop_id === $shop->id, 404);

        return (new SupplierResource($supplier))->response();
    }

    public function update(UpdateSupplierRequest $request, Shop $shop, Supplier $supplier): JsonResponse
    {
        $this->authorizeShop($request, $shop);
        abort_unless($supplier->shop_id === $shop->id, 404);

        $supplier->update($request->validated());

        return (new SupplierResource($supplier->fresh()))->response();
    }

    public function destroy(Request $request, Shop $shop, Supplier $supplier): JsonResponse
    {
        $this->authorizeShop($request, $shop);
        abort_unless($supplier->shop_id === $shop->id, 404);

        $supplier->delete();

        return response()->json(['message' => 'Fournisseur supprimé.']);
    }

    private function authorizeShop(Request $request, Shop $shop): void
    {
        $user = $request->user();
        abort_unless($user->is_super_admin || $shop->owner_id === $user->id, 403);
    }
}
