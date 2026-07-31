<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attribute_type_id' => $this->attribute_type_id,
            'value' => $this->value,
            'hex_color' => $this->hex_color,
        ];
    }
}
