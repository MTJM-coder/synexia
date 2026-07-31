<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Catalog\Http\Requests\StoreAttributeTypeRequest;
use Modules\Catalog\Http\Requests\UpdateAttributeTypeRequest;
use Modules\Catalog\Http\Resources\AttributeTypeResource;
use Modules\Catalog\Models\AttributeType;
use Modules\Catalog\Policies\ProductPolicy;
use Modules\Marketplace\Models\Shop;

class AttributeTypeController extends Controller
{
    public function __construct(
        private readonly ProductPolicy $policy,
    ) {
    }

    public function index(Shop $shop): JsonResponse
    {
        $types = AttributeType::with('values')->where('shop_id', $shop->id)->get();

        return AttributeTypeResource::collection($types)->response();
    }

    public function store(StoreAttributeTypeRequest $request, Shop $shop): JsonResponse
    {
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $type = AttributeType::create([...$request->validated(), 'shop_id' => $shop->id]);

        return (new AttributeTypeResource($type))->response()->setStatusCode(201);
    }

    public function update(UpdateAttributeTypeRequest $request, Shop $shop, AttributeType $attributeType): JsonResponse
    {
        abort_unless($attributeType->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $attributeType->update($request->validated());

        return (new AttributeTypeResource($attributeType->fresh()))->response();
    }

    public function destroy(Request $request, Shop $shop, AttributeType $attributeType): JsonResponse
    {
        abort_unless($attributeType->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        // CORRIGÉ : attribute_values.attribute_type_id est aussi en
        // cascadeOnDelete() — supprimer un type ("Couleur") supprimerait en
        // cascade toutes ses valeurs, elles-mêmes retirées silencieusement
        // de toute variante les utilisant. Même garde-fou que sur
        // AttributeValueController::destroy(), un cran au-dessus.
        $hasValuesInUse = $attributeType->values()
            ->whereHas('variants')
            ->exists();

        if ($hasValuesInUse) {
            return response()->json([
                'message' => 'Impossible de supprimer ce type d\'attribut : au moins une de ses valeurs est utilisée par une variante existante.',
            ], 422);
        }

        $attributeType->delete();

        return response()->json(['message' => 'Type d\'attribut supprimé.']);
    }
}
