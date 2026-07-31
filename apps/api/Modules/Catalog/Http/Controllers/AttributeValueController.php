<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Catalog\Http\Requests\StoreAttributeValueRequest;
use Modules\Catalog\Http\Requests\UpdateAttributeValueRequest;
use Modules\Catalog\Http\Resources\AttributeValueResource;
use Modules\Catalog\Models\AttributeType;
use Modules\Catalog\Models\AttributeValue;
use Modules\Catalog\Policies\ProductPolicy;
use Modules\Marketplace\Models\Shop;

class AttributeValueController extends Controller
{
    public function __construct(
        private readonly ProductPolicy $policy,
    ) {
    }

    public function store(StoreAttributeValueRequest $request, Shop $shop, AttributeType $attributeType): JsonResponse
    {
        abort_unless($attributeType->shop_id === $shop->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $value = AttributeValue::create([
            ...$request->validated(),
            'attribute_type_id' => $attributeType->id,
        ]);

        return (new AttributeValueResource($value))->response()->setStatusCode(201);
    }

    public function update(
        UpdateAttributeValueRequest $request,
        Shop $shop,
        AttributeType $attributeType,
        AttributeValue $value,
    ): JsonResponse {
        abort_unless($attributeType->shop_id === $shop->id, 404);
        abort_unless($value->attribute_type_id === $attributeType->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        $value->update($request->validated());

        return (new AttributeValueResource($value->fresh()))->response();
    }

    public function destroy(Request $request, Shop $shop, AttributeType $attributeType, AttributeValue $value): JsonResponse
    {
        abort_unless($attributeType->shop_id === $shop->id, 404);
        abort_unless($value->attribute_type_id === $attributeType->id, 404);
        abort_unless($this->policy->manage($request->user(), $shop->id), 403);

        // CORRIGÉ : product_variant_attribute_values.attribute_value_id est
        // en cascadeOnDelete() — sans cette vérification, supprimer "Noir"
        // retirait silencieusement cette valeur de toutes les variantes qui
        // l'utilisaient (une variante "Noir-S" devenait juste "S"), sans
        // aucun avertissement et avec un risque de collision de signature.
        if ($value->variants()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette valeur : elle est utilisée par au moins une variante existante.',
            ], 422);
        }

        $value->delete();

        return response()->json(['message' => 'Valeur supprimée.']);
    }
}
