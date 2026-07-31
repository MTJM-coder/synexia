<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => (float) $this->price,
            'compare_at_price' => $this->compare_at_price !== null ? (float) $this->compare_at_price : null,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'attribute_values' => $this->whenLoaded('attributeValues', fn () => $this->attributeValues->map(fn ($value) => [
                'id' => $value->id,
                'value' => $value->value,
                'hex_color' => $value->hex_color,
                'attribute_type' => $value->relationLoaded('attributeType') ? $value->attributeType->name : null,
            ])),
        ];
    }
}
